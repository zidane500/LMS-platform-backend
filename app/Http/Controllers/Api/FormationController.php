<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\ModuleFormation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\Request;

class FormationController extends Controller
{
    // ─── US 2.3 : Lister et filtrer les formations ────────
    public function index(Request $request)
    {
        $query = Formation::with(['formateur', 'modules']);

        // Filtre par statut (par défaut : formations publiées seulement)
        // Les admins/formateurs voient aussi leurs brouillons
        $user = $request->user();
        if ($user && in_array($user->role, ['admin', 'formateur'])) {
            if ($request->statut) {
                $query->where('statut', $request->statut);
            }
            // Le formateur ne voit que ses propres formations si filtre 'mine'
            if ($request->mine === 'true' && $user->role === 'formateur') {
                $query->where('formateur_id', $user->id);
            }
        } else {
            // Les apprenants ne voient que les formations publiées
            $query->where('statut', 'publie');
        }

        // Recherche par titre ou description
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'ilike', "%$search%")
                  ->orWhere('description', 'ilike', "%$search%");
            });
        }

        // Filtre par catégorie
        if ($request->categorie && $request->categorie !== 'all') {
            $query->where('categorie', $request->categorie);
        }

        // Filtre par niveau
        if ($request->niveau && $request->niveau !== 'all') {
            $query->where('niveau', $request->niveau);
        }

        $formations = $query->orderBy('created_at', 'desc')->get();

        // Pour chaque formation, vérifier si l'utilisateur est inscrit
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

    // ─── US 2.4 : Détail d'une formation ──────────────────
    public function show(Request $request, $id)
    {
        $formation = Formation::with(['formateur', 'modules'])->findOrFail($id);

        $inscriptions = [];
        if ($request->user()) {
            $inscriptions = Inscription::where('user_id', $request->user()->id)
                ->pluck('formation_id')
                ->toArray();
        }

        return response()->json($this->formatFormation($formation, $inscriptions));
    }

    // ─── US 2.1 : Créer une formation ─────────────────────
    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorize_instructor_or_admin($user);

        $validated = $request->validate([
            'titre'         => 'required|string|max:255',
            'description'   => 'required|string',
            'categorie'     => 'required|string|max:100',
            'niveau'        => 'required|in:debutant,intermediaire,avance',
            'duree_estimee' => 'required|integer|min:1',
            'prerequis'     => 'nullable|array',
            'miniature'     => 'nullable|string|max:500',
            'statut'        => 'nullable|in:brouillon,publie',
        ]);

        $formation = Formation::create([
            ...$validated,
            'formateur_id' => $user->id,
            'statut'       => $validated['statut'] ?? 'brouillon',
        ]);

        return response()->json([
            'message'   => 'Formation créée avec succès',
            'formation' => $this->formatFormation($formation->load('formateur', 'modules'), []),
        ], 201);
    }

    // ─── US 2.1 (modifier) : Mettre à jour une formation ──
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($id);

        // Seul le formateur propriétaire ou un admin peut modifier
        $this->authorize_owner_or_admin($user, $formation);

        $validated = $request->validate([
            'titre'         => 'sometimes|required|string|max:255',
            'description'   => 'sometimes|required|string',
            'categorie'     => 'sometimes|required|string|max:100',
            'niveau'        => 'sometimes|required|in:debutant,intermediaire,avance',
            'duree_estimee' => 'sometimes|required|integer|min:1',
            'prerequis'     => 'nullable|array',
            'miniature'     => 'nullable|string|max:500',
            'statut'        => 'sometimes|required|in:brouillon,publie',
        ]);

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

        // Récupérer les IDs des apprenants inscrits avant suppression
        // (pour notification - pour l'instant on les retourne juste)
        $nbInscrits = $formation->inscriptions()->count();

        // La suppression cascade grâce aux FK (supprime modules + inscriptions)
        $formation->delete();

        return response()->json([
            'message'    => 'Formation supprimée avec succès',
            'nb_inscrits' => $nbInscrits,
        ]);
    }

    // ─── Inscription à une formation ──────────────────────
    public function enroll(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'apprenant') {
            return response()->json(['message' => 'Seuls les apprenants peuvent s\'inscrire.'], 403);
        }

        $formation = Formation::findOrFail($id);

        if ($formation->statut !== 'publie') {
            return response()->json(['message' => 'Cette formation n\'est pas disponible.'], 400);
        }

        // Vérifier si déjà inscrit
        $already = Inscription::where('user_id', $user->id)
                              ->where('formation_id', $id)
                              ->exists();

        if ($already) {
            return response()->json(['message' => 'Vous êtes déjà inscrit à cette formation.'], 409);
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
