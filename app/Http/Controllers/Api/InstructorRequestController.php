<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeFormateur;
use App\Models\Formateur;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstructorRequestController extends Controller
{
    // ─── US 1.4 : Soumettre une demande pour devenir formateur ──
    public function store(Request $request)
    {
        $user = $request->user();

        // Seuls les apprenants peuvent faire une demande
        if ($user->role !== 'apprenant') {
            return response()->json([
                'message' => 'Seuls les apprenants peuvent soumettre cette demande.',
            ], 403);
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente
        $existing = DemandeFormateur::where('user_id', $user->id)
                                    ->where('statut', 'en_attente')
                                    ->first();
        if ($existing) {
            return response()->json([
                'message' => 'Vous avez déjà une demande en attente.',
            ], 409);
        }

        // Validation
        $request->validate([
            'specialite'         => 'required|string|max:200',
            'experience_annees'  => 'required|integer|min:0|max:50',
            'motivation'         => 'required|string|:50',
            'langues_enseignees' => 'required|array|min:1',
            'cv'            => 'required|array|min:1',
            'cv.*'          => 'file|mimes:pdf|max:5120',        
            'attestation'   => 'required|array|min:1',
            'attestation.*' => 'file|mimes:pdf|max:5120',
        ]);

        // Sauvegarder les fichiers PDF
        $cheminsCv = [];
        foreach ($request->file('cv') as $file) {
        $cheminsCv[] = $file->store('demandes/cv', 'public');
        }

        $cheminsAttestation = [];
        foreach ($request->file('attestation') as $file) {
        $cheminsAttestation[] = $file->store('demandes/attestations', 'public');
        }

        // Créer la demande
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

        // Filtre par statut
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
            'action' => 'required|in:accepter,refuser',
        ]);

        $demande = DemandeFormateur::with('user')->findOrFail($id);

        if ($demande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 409);
        }

        if ($request->action === 'accepter') {
            // Mettre à jour le statut de la demande
            $demande->statut         = 'acceptee';
            $demande->admin_id       = $request->user()->id;
            $demande->date_traitement = now();
            $demande->save();

            // Changer le rôle de l'utilisateur → formateur
            $demande->user->role = 'formateur';
            $demande->user->save();

            // Créer l'entrée dans la table formateurs
            Formateur::updateOrCreate(
                ['user_id' => $demande->user_id],
                [
                    'specialite'         => $demande->specialite,
                    'experience_annees'  => $demande->experience_annees,
                    'langues_enseignees' => $demande->langues_enseignees,
                ]
            );

            return response()->json([
                'message' => 'Demande acceptée. L\'utilisateur est maintenant formateur.',
                'demande' => $this->formatDemande($demande),
            ]);
        } else {
            // Refus
            $demande->statut          = 'refusee';
            $demande->admin_id        = $request->user()->id;
            $demande->date_traitement = now();
            $demande->save();

            return response()->json([
                'message' => 'Demande refusée. L\'utilisateur a été notifié.',
                'demande' => $this->formatDemande($demande),
            ]);
        }
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

    // ✅ Retourner l'URL publique directement
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
            'cv_urls' => $d->chemin_cv 
            ? array_map(fn($p) => asset('storage/' . $p), json_decode($d->chemin_cv, true) ?? [])
            : [],
            'attestation_urls' => $d->chemin_attestation
            ? array_map(fn($p) => asset('storage/' . $p), json_decode($d->chemin_attestation, true) ?? [])
            : [],
            'date_demande'       => $d->date_demande?->toISOString(),
            'date_traitement'    => $d->date_traitement?->toISOString(),
        ];
    }
}
