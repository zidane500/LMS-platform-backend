<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeFormateur;
use App\Models\Formateur;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InstructorRequestController extends Controller
{
    // ─── US 1.4 : Soumettre une demande pour devenir formateur ──
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'apprenant') {
            return response()->json([
                'message' => 'Seuls les apprenants peuvent soumettre cette demande.',
            ], 403);
        }

        $existing = DemandeFormateur::where('user_id', $user->id)
                                    ->where('statut', 'en_attente')
                                    ->first();
        if ($existing) {
            return response()->json([
                'message' => 'Vous avez déjà une demande en attente.',
            ], 409);
        }

        $request->validate([
            'specialite'         => 'required|string|max:200',
            'experience_annees'  => 'required|integer|min:0|max:50',
            'motivation'         => 'required|string|min:50',
            'langues_enseignees' => 'required|array|min:1',
            // ✅ Fix 1 — Limite : 5 CV max, 10 attestations max
            'cv'                 => 'required|array|min:1|max:5',
            'cv.*'               => 'file|mimes:pdf|max:5120',
            'attestation'        => 'required|array|min:1|max:10',
            'attestation.*'      => 'file|mimes:pdf|max:5120',
        ]);

        // ✅ Sauvegarder TOUS les fichiers CV
        $cheminsCv = [];
        foreach ($request->file('cv') as $file) {
            $cheminsCv[] = $file->store('demandes/cv', 'public');
        }

        // ✅ Sauvegarder TOUTES les attestations
        $cheminsAttestation = [];
        foreach ($request->file('attestation') as $file) {
            $cheminsAttestation[] = $file->store('demandes/attestations', 'public');
        }

        $demande = DemandeFormateur::create([
            'user_id'            => $user->id,
            'specialite'         => $request->specialite,
            'experience_annees'  => $request->experience_annees,
            'motivation'         => $request->motivation,
            'langues_enseignees' => $request->langues_enseignees,
            'chemin_cv'          => json_encode($cheminsCv),
            'chemin_attestation' => json_encode($cheminsAttestation),
            'statut'             => 'en_attente',
        ]);

        NotificationService::notifyAdmins(
            "📋 Nouvelle demande de formateur de {$user->prenom} {$user->nom} ({$user->email})",
            'info'
        );

        return response()->json([
            'message' => 'Demande envoyée avec succès. Vous serez notifié de la décision.',
            'demande' => $this->formatDemande($demande->load('user')),
        ], 201);
    }

    // ─── US 1.4 : Voir le statut de sa propre demande ───────────
    public function myRequest(Request $request)
    {
        $demande = DemandeFormateur::where('user_id', $request->user()->id)
                                   ->latest('date_demande')
                                   ->first();

        if (!$demande) {
            return response()->json(['demande' => null]);
        }

        return response()->json(['demande' => $this->formatDemande($demande)]);
    }

    // ─── US 1.5 (admin) : Lister toutes les demandes ────────────
    public function index(Request $request)
    {
        $this->authorize_admin($request->user());

        $query = DemandeFormateur::with('user');

        if ($request->has('statut') && $request->statut !== 'all') {
            $query->where('statut', $request->statut);
        }

        $demandes = $query->orderBy('date_demande', 'desc')->get();

        return response()->json(
            $demandes->map(fn($d) => $this->formatDemande($d))
        );
    }

    // ─── US 1.5 (admin) : Accepter ou refuser une demande ───────
    public function process(Request $request, $id)
    {
        $this->authorize_admin($request->user());

        $request->validate([
            'action'            => 'required|in:accepter,refuser',
            'commentaire_admin' => 'nullable|string|max:500',
        ]);

        $demande = DemandeFormateur::with('user')->findOrFail($id);

        if ($demande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 409);
        }

        $user = $demande->user;
        $nom  = $user->prenom . ' ' . $user->nom;

        if ($request->action === 'accepter') {

            $demande->statut          = 'acceptee';
            $demande->admin_id        = $request->user()->id;
            $demande->date_traitement = now();
            $demande->save();

            $user->role = 'formateur';
            $user->save();

            \App\Models\Apprenant::firstOrCreate(
                ['user_id' => $demande->user_id],
                ['domaines_cibles' => [], 'technologies' => []]
            );

            Formateur::updateOrCreate(
                ['user_id' => $demande->user_id],
                [
                    'specialite'         => $demande->specialite,
                    'experience_annees'  => $demande->experience_annees,
                    'langues_enseignees' => $demande->langues_enseignees,
                ]
            );

            // ✅ Notification acceptation — type 'info'
            NotificationService::send(
                $user->id,
                "Votre demande de devenir formateur a été approuvée. Bienvenue dans l'équipe pédagogique !",
                'info'
            );

            $this->envoyerEmailDecision(
                user:        $user,
                nom:         $nom,
                approuve:    true,
                commentaire: $request->commentaire_admin ?? 'Votre profil correspond à nos critères.'
            );

            return response()->json([
                'message' => 'Demande acceptée. L\'utilisateur a été notifié.',
                'demande' => $this->formatDemande($demande),
            ]);

        } else {

            $demande->statut          = 'refusee';
            $demande->admin_id        = $request->user()->id;
            $demande->date_traitement = now();
            $demande->save();

            $motif = $request->commentaire_admin ?? 'Non précisé';

            // ✅ Fix 2 — Notification refus aussi en type 'info' (pas 'warning')
            NotificationService::send(
                $user->id,
                "Votre demande de devenir formateur a été refusée.  \nMotif : {$motif}",
                'info'
            );

            $this->envoyerEmailDecision(
                user:        $user,
                nom:         $nom,
                approuve:    false,
                commentaire: $motif
            );

            return response()->json([
                'message' => 'Demande refusée. L\'utilisateur a été notifié.',
                'demande' => $this->formatDemande($demande),
            ]);
        }
    }

    // ─── Email de décision envoyé à l'apprenant ──────────────────
    private function envoyerEmailDecision(
        User   $user,
        string $nom,
        bool   $approuve,
        string $commentaire
    ): void {
        $statutLabel = $approuve ? 'approuvée' : 'refusée';
        $couleur     = $approuve ? '#16a34a' : '#dc2626';

        Mail::send([], [], function ($mail) use (
            $user, $nom, $approuve, $statutLabel, $commentaire, $couleur
        ) {
            $mail->to($user->email)
                 ->subject("Résultat de votre demande de formateur")
                 ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px;
                                margin: 0 auto; padding: 24px;'>
                        <h2 style='color: {$couleur};'>
                            Votre demande a été <u>{$statutLabel}</u>
                        </h2>
                        <p>Bonjour <strong>{$nom}</strong>,</p>
                        " . ($approuve ? "
                        <p>Félicitations ! Votre demande de devenir formateur a été
                           <strong style='color:#16a34a;'>approuvée</strong>.
                           Vous pouvez maintenant créer et gérer des formations
                           sur la plateforme.</p>
                        " : "
                        <p>Nous avons examiné votre demande de devenir formateur.
                           Malheureusement, elle a été
                           <strong style='color:#dc2626;'>refusée</strong>.</p>
                        ") . "
                        <div style='background:#f3f4f6; padding:15px;
                                    border-left:4px solid {$couleur};
                                    border-radius:4px; margin:20px 0;'>
                            <p style='margin:0; font-weight:bold;'>
                                Commentaire de l'administrateur :
                            </p>
                            <p style='margin:8px 0 0;'>{$commentaire}</p>
                        </div>
                        <p style='color:#6b7280; font-size:13px;'>
                            L'équipe LMS Platform
                        </p>
                    </div>
                 ");
        });
    }

    // ─── Accès aux fichiers PDF ──────────────────────────────────
    public function downloadFile(Request $request, $id, $type)
    {
        $this->authorize_admin($request->user());

        $demande = DemandeFormateur::findOrFail($id);

        $chemins = $type === 'cv'
            ? json_decode($demande->chemin_cv, true)
            : json_decode($demande->chemin_attestation, true);

        $chemin = is_array($chemins) ? ($chemins[0] ?? null) : $chemins;

        if (!$chemin || !Storage::disk('public')->exists($chemin)) {
            return response()->json(['message' => 'Fichier non trouvé'], 404);
        }

        return response()->json([
            'url' => asset('storage/' . $chemin),
        ]);
    }

    // ─── Méthodes privées ────────────────────────────────────────
    private function authorize_admin(User $user): void
    {
        if ($user->role !== 'admin') {
            abort(403, 'Accès réservé aux administrateurs.');
        }
    }

    private function formatDemande(DemandeFormateur $d): array
    {
        return [
            'id'                 => (string) $d->id,
            'user_id'            => (string) $d->user_id,
            'user'               => $d->relationLoaded('user') ? [
                'id'     => (string) $d->user->id,
                'prenom' => $d->user->prenom,
                'nom'    => $d->user->nom,
                'email'  => $d->user->email,
            ] : null,
            'specialite'         => $d->specialite,
            'experience_annees'  => $d->experience_annees,
            'motivation'         => $d->motivation,
            'langues_enseignees' => $d->langues_enseignees ?? [],
            'statut'             => $d->statut,
            // ✅ Retourne TOUS les fichiers CV (tableau complet)
            'cv_urls'            => $d->chemin_cv
                ? array_map(
                    fn($p) => asset('storage/' . $p),
                    json_decode($d->chemin_cv, true) ?? []
                  )
                : [],
            // ✅ Retourne TOUTES les attestations (tableau complet)
            'attestation_urls'   => $d->chemin_attestation
                ? array_map(
                    fn($p) => asset('storage/' . $p),
                    json_decode($d->chemin_attestation, true) ?? []
                  )
                : [],
            'date_demande'       => $d->date_demande?->toISOString(),
            'date_traitement'    => $d->date_traitement?->toISOString(),
        ];
    }
}