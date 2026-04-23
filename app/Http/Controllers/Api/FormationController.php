<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\ModuleFormation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class FormationController extends Controller
{
    // ─── US 2.3 : Lister et filtrer les formations ────────
    public function index(Request $request)
{
    $query = Formation::with(['formateur', 'modules']);

    $user = auth('sanctum')->user();

    if ($user && in_array($user->role, ['admin', 'formateur'])) {
        // Brouillons séparés des publiés
        if ($request->statut === 'brouillon') {
            $query->where('statut', 'brouillon');
            // Formateur voit SEULEMENT ses propres brouillons
            if ($user->role === 'formateur') {
                $query->where('formateur_id', $user->id);
            }
        } else {
            // Par défaut : seulement les publiés
            $query->where('statut', 'publie');
        }

        if ($request->mine === 'true') {
            $query->where('formateur_id', $user->id);
        }
    } else {
        // Apprenants / visiteurs : uniquement publiés
        $query->where('statut', 'publie');
    }

    if ($request->search) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('titre', 'ilike', "%$search%")
              ->orWhere('description', 'ilike', "%$search%");
        });
    }

    if ($request->categorie && $request->categorie !== 'all') {
        $query->where('categorie', $request->categorie);
    }

    if ($request->niveau && $request->niveau !== 'all') {
        $query->where('niveau', $request->niveau);
    }

    // ── Nouveau : filtre par formateur ──
    if ($request->formateur_id && $request->formateur_id !== 'all') {
        $query->where('formateur_id', $request->formateur_id);
    }

    $formations = $query->orderBy('created_at', 'desc')->get();

    $inscriptions = [];
    if ($user) {
        $inscriptions = Inscription::where('user_id', $user->id)
            ->pluck('formation_id')
            ->toArray();
    }

    return response()->json(
        $formations->map(fn($f) => $this->formatFormation($f, $inscriptions))
    );
}

// ─── Récupérer les formateurs ayant des formations publiées ──
public function instructors()
{
    $instructors = User::whereHas('formations', function ($q) {
        $q->where('statut', 'publie');
    })
    ->get(['id', 'prenom', 'nom'])
    ->map(fn($u) => [
        'id'     => (string) $u->id,
        'prenom' => $u->prenom,
        'nom'    => $u->nom,
    ]);

    return response()->json($instructors);
}


    // ─── US 2.4 : Détail d'une formation ──────────────────
    // ✅ FIX : utilise auth('sanctum') au lieu de $request->user()
    // pour que est_inscrit soit correct même sur route publique
    public function show(Request $request, $id)
    {
        $formation = Formation::with(['formateur', 'modules'])->findOrFail($id);

        $user = auth('sanctum')->user(); // ← FIX ICI

        $inscriptions = [];
        if ($user) {
            $inscriptions = Inscription::where('user_id', $user->id)
                ->pluck('formation_id')
                ->toArray();
        }

        return response()->json($this->formatFormation($formation, $inscriptions));
    }

    // ─── US 2.1 : Créer une formation ─────────────────────
    public function store(Request $request)
{
    $user = $request->user();

    $validated = $request->validate([
        'titre' => 'required|string|max:255',
        'description' => 'required|string',
        'categorie' => 'required|string|max:100',
        'niveau' => 'required|in:debutant,intermediaire,avance',
        'duree_estimee' => 'required|integer|min:1',
        'prerequis' => 'nullable|array',
        'miniature_fichier' => 'nullable|image|max:5120',
        'statut' => 'nullable|in:brouillon,publie',
    ]);

    $miniatureUrl = null;

    // ✅ Upload image
    if ($request->hasFile('miniature_fichier')) {
        $file = $request->file('miniature_fichier');

        $chemin = $file->store('formations/miniatures', 'public');

        $miniatureUrl = asset('storage/' . $chemin);
    }

    // ✅ IMPORTANT : ne pas utiliser ...$validated directement
    $formation = Formation::create([
        'titre' => $validated['titre'],
        'description' => $validated['description'],
        'categorie' => $validated['categorie'],
        'niveau' => $validated['niveau'],
        'duree_estimee' => $validated['duree_estimee'],
        'prerequis' => $validated['prerequis'] ?? [],
        'miniature' => $miniatureUrl, // ✅ ICI
        'statut' => $validated['statut'] ?? 'brouillon',
        'formateur_id' => $user->id,
    ]);

    

    // ✅ NOUVEAU — Notifier admins + tous les utilisateurs (info)
\App\Services\NotificationService::notifyAdmins(
    "📚 Nouvelle formation créée : \"{$formation->titre}\" par {$user->prenom} {$user->nom}",
    'info'
);

// ✅ Notifier tous les apprenants et formateurs (sauf le créateur)
$destinataires = \App\Models\User::whereIn('role', ['apprenant', 'formateur'])
    ->where('id', '!=', $user->id)
    ->get();

foreach ($destinataires as $dest) {
    \App\Services\NotificationService::send(
        $dest->id,
        "📚 Nouvelle formation disponible : \"{$formation->titre}\"",
        'info'
    );
}

return response()->json([
        'message' => 'Formation créée avec succès',
        'formation' => $formation,
    ], 201);

}

    // ─── US 2.1 (modifier) : Mettre à jour une formation ──
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($id);

        $this->authorize_owner_or_admin($user, $formation);

        $validated = $request->validate([
            'titre'         => 'sometimes|required|string|max:255',
            'description'   => 'sometimes|required|string',
            'categorie'     => 'sometimes|required|string|max:100',
            'niveau'        => 'sometimes|required|in:debutant,intermediaire,avance',
            'duree_estimee' => 'sometimes|required|integer|min:1',
            'prerequis'     => 'nullable|array',
            'miniature'     => 'nullable|string|max:500',
            'miniature_fichier'=> 'nullable|image|max:5120',
            'statut'        => 'sometimes|required|in:brouillon,publie',
        ]);

        if ($request->hasFile('miniature_fichier')) {
    // Supprimer l'ancienne miniature si c'était un fichier uploadé
    if ($formation->miniature && str_contains($formation->miniature, '/storage/formations/')) {
        $ancienChemin = str_replace(asset('storage/'), '', $formation->miniature);
        Storage::disk('public')->delete($ancienChemin);
    }
    $chemin = $request->file('miniature_fichier')
        ->store('formations/miniatures', 'public');
    $validated['miniature'] = asset('storage/' . $chemin);
}

        $formation->update($validated);

        return response()->json([
            'message'   => 'Formation mise à jour',
            'formation' => $this->formatFormation($formation->load('formateur', 'modules'), []),
        ]);
    }

    // ─── US 2.6 : Supprimer une formation ─────────────────
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($id);

        $this->authorize_owner_or_admin($user, $formation);

        $nbInscrits = $formation->inscriptions()->count();
        $formation->delete();

        return response()->json([
            'message'     => 'Formation supprimée avec succès',
            'nb_inscrits' => $nbInscrits,
        ]);
    }

    // ─── Inscription à une formation ──────────────────────
    public function enroll(Request $request, $id)
{
    $user = $request->user();

    // ✅ Apprenants ET formateurs peuvent s'inscrire
    if (!in_array($user->role, ['apprenant', 'formateur'])) {
        return response()->json([
            'message' => 'Seuls les apprenants et formateurs peuvent s\'inscrire.',
        ], 403);
    }

    $formation = Formation::findOrFail($id);

    // ✅ Un formateur ne peut pas s'inscrire à SA PROPRE formation
    if ($user->role === 'formateur' && $formation->formateur_id === $user->id) {
        return response()->json([
            'message' => 'Vous êtes le créateur de cette formation, vous y avez accès direct sans inscription.',
        ], 403);
    }

    if ($formation->statut !== 'publie') {
        return response()->json([
            'message' => 'Cette formation n\'est pas disponible.',
        ], 400);
    }

    $already = Inscription::where('user_id', $user->id)
                          ->where('formation_id', $id)
                          ->exists();

    if ($already) {
        return response()->json([
            'message' => 'Vous êtes déjà inscrit à cette formation.',
        ], 409);
    }

    Inscription::create([
        'user_id'      => $user->id,
        'formation_id' => $id,
    ]);

    return response()->json(['message' => 'Inscription réussie !'], 201);
}

    // ─── Récupérer les catégories disponibles ─────────────
    public function categories()
    {
        $cats = Formation::where('statut', 'publie')
            ->distinct()
            ->pluck('categorie')
            ->filter()
            ->values();

        return response()->json($cats);
    }

    // ─── Méthodes privées ─────────────────────────────────
    private function authorize_instructor_or_admin(User $user): void
    {
        if (!in_array($user->role, ['formateur', 'admin'])) {
            abort(403, 'Accès réservé aux formateurs et administrateurs.');
        }
    }

    private function authorize_owner_or_admin(User $user, Formation $formation): void
    {
        if ($user->role === 'admin') return;
        if ($user->role === 'formateur' && $formation->formateur_id === $user->id) return;
        abort(403, 'Vous n\'êtes pas autorisé à modifier cette formation.');
    }

    private function formatFormation(Formation $f, array $inscriptionsUtilisateur): array
    {
        return [
            'id'            => (string) $f->id,
            'titre'         => $f->titre,
            'description'   => $f->description,
            'categorie'     => $f->categorie,
            'niveau'        => $f->niveau,
            'duree_estimee' => $f->duree_estimee,
            'prerequis'     => $f->prerequis ?? [],
            'miniature'     => $f->miniature,
            'statut'        => $f->statut,
            'formateur_id'  => (string) $f->formateur_id,
            'formateur'     => $f->relationLoaded('formateur') && $f->formateur ? [
                'id'     => (string) $f->formateur->id,
                'prenom' => $f->formateur->prenom,
                'nom'    => $f->formateur->nom,
                'email'  => $f->formateur->email,
            ] : null,
            'modules'       => $f->relationLoaded('modules')
                ? $f->modules->map(fn($m) => [
                    'id'          => (string) $m->id,
                    'titre'       => $m->titre,
                    'description' => $m->description,
                    'duree'       => $m->duree,
                    'ordre'       => $m->ordre,
                ])->toArray()
                : [],
            'est_inscrit'   => in_array($f->id, $inscriptionsUtilisateur),
            'created_at'    => $f->created_at?->toISOString(),
        ];
    }
}
