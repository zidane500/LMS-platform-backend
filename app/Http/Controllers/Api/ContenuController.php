<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contenu;
use App\Models\Formation;
use App\Models\ModuleFormation;
use App\Models\ProgressionContenu;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\BadgeService;
use App\Models\ProgressionFormation;
use Illuminate\Support\Facades\Storage;

class ContenuController extends Controller
{
    // ─── US 3.2 : Lister les contenus d'un module ─────────
    public function index(Request $request, $formationId, $moduleId)
    {
        $user   = auth('sanctum')->user();
        $module = ModuleFormation::where('formation_id', $formationId)
                                 ->findOrFail($moduleId);

        $contenus = Contenu::where('module_id', $moduleId)
                           ->orderBy('ordre')
                           ->get();

        // Récupérer les progressions de l'utilisateur connecté
        $progressions = [];
        if ($user) {
            $progressions = ProgressionContenu::where('user_id', $user->id)
                ->whereIn('contenu_id', $contenus->pluck('id'))
                ->get()
                ->keyBy('contenu_id');
        }

        return response()->json(
            $contenus->map(fn($c) => $this->formatContenu($c, $progressions[$c->id] ?? null))
        );
    }

    // ─── US 3.1 : Ajouter un contenu ──────────────────────
    public function store(Request $request, $formationId, $moduleId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);
        ModuleFormation::where('formation_id', $formationId)->findOrFail($moduleId);

        $this->authorize_owner_or_admin($user, $formation);
        
        $formation = Formation::findOrFail($formationId); // nécessaire pour le titre


        $request->validate([
            'titre'     => 'required|string|max:255',
            'type'      => 'required|in:video,pdf,audio,scorm',
            'url'       => 'nullable|string|max:1000',
            'fichier'   => 'nullable|file|max:102400', // 100MB max
            'duree'     => 'nullable|integer|min:0',
            'resume'    => 'nullable|string',
            'miniature' => 'nullable|string|max:500',
        ]);

        $cheminFichier = null;
        if ($request->hasFile('fichier')) {
            $cheminFichier = $request->file('fichier')
                ->store("contenus/{$formationId}/{$moduleId}", 'public');
        }

        $maxOrdre = Contenu::where('module_id', $moduleId)->max('ordre') ?? 0;

        $contenu = Contenu::create([
            'module_id'      => $moduleId,
            'titre'          => $request->titre,
            'type'           => $request->type,
            'url'            => $request->url,
            'chemin_fichier' => $cheminFichier,
            'duree'          => $request->duree ?? 0,
            'resume'         => $request->resume,
            'miniature'      => $request->miniature,
            'ordre'          => $maxOrdre + 1,
        ]);

       
      /* 
        // Notifier uniquement les utilisateurs inscrits à cette formation
$inscrits = \App\Models\Inscription::where('formation_id', $formationId)
    ->with('user')
    ->get();

    foreach ($inscrits as $inscription) {
    // Ne pas notifier le formateur créateur du contenu
    if ($inscription->user_id !== $user->id) {
        \App\Services\NotificationService::send(
            $inscription->user_id,
            "📖 Nouveau contenu ajouté dans la formation \"{$formation->titre}\" : \"{$contenu->titre}\"",
            'info'
        );
    }
}
*/

        return response()->json([
            'message' => 'Contenu ajouté avec succès',
            'contenu' => $this->formatContenu($contenu),
        ], 201);
    }

    // ─── US 3.3 : Modifier un contenu ─────────────────────
    public function update(Request $request, $formationId, $moduleId, $contenuId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);
        $contenu   = Contenu::where('module_id', $moduleId)->findOrFail($contenuId);

        $this->authorize_owner_or_admin($user, $formation);

        $request->validate([
            'titre'     => 'sometimes|required|string|max:255',
            'type'      => 'sometimes|required|in:video,pdf,audio,scorm',
            'url'       => 'nullable|string|max:1000',
            'fichier'   => 'nullable|file|max:102400',
            'duree'     => 'nullable|integer|min:0',
            'resume'    => 'nullable|string',
            'miniature' => 'nullable|string|max:500',
        ]);

        // Nouveau fichier uploadé → supprimer l'ancien
        if ($request->hasFile('fichier')) {
            if ($contenu->chemin_fichier) {
                Storage::disk('public')->delete($contenu->chemin_fichier);
            }
            $contenu->chemin_fichier = $request->file('fichier')
                ->store("contenus/{$formationId}/{$moduleId}", 'public');
            $contenu->url = null;
        }

        if ($request->has('titre'))     $contenu->titre     = $request->titre;
        if ($request->has('type'))      $contenu->type      = $request->type;
        if ($request->has('url') && !$request->hasFile('fichier')) {
            $contenu->url            = $request->url;
            $contenu->chemin_fichier = null;
        }
        if ($request->has('duree'))     $contenu->duree     = $request->duree;
        if ($request->has('resume'))    $contenu->resume    = $request->resume;
        if ($request->has('miniature')) $contenu->miniature = $request->miniature;

        $contenu->save();

        return response()->json([
            'message' => 'Contenu mis à jour',
            'contenu' => $this->formatContenu($contenu),
        ]);
    }

    // ─── US 3.4 : Supprimer un contenu ────────────────────
    public function destroy(Request $request, $formationId, $moduleId, $contenuId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);
        $contenu   = Contenu::where('module_id', $moduleId)->findOrFail($contenuId);

        $this->authorize_owner_or_admin($user, $formation);

        // Supprimer le fichier physique si uploadé
        if ($contenu->chemin_fichier) {
            Storage::disk('public')->delete($contenu->chemin_fichier);
        }

        $contenu->delete();

        return response()->json(['message' => 'Contenu supprimé avec succès']);
    }

    // ─── US 3.2 : Marquer un contenu comme consulté ───────


public function marquerConsulte(Request $request, $formationId, $moduleId, $contenuId)
{
    $user = $request->user();

    // ✅ Seuls apprenant ET formateur inscrit peuvent marquer
    if (!in_array($user->role, ['apprenant', 'formateur'])) {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

    $contenu = \App\Models\Contenu::findOrFail($contenuId);
    $pourcentage = $request->input('pourcentage', 100);

    // Mettre à jour ou créer la progression du contenu
    \App\Models\ProgressionContenu::updateOrCreate(
        ['user_id' => $user->id, 'contenu_id' => $contenuId],
        [
            'complete'               => $pourcentage >= 100,
            'pourcentage'            => min(100, $pourcentage),
            'derniere_consultation'  => now(),
        ]
    );

    // ✅ Mettre à jour progression_formations
    $formation = \App\Models\Formation::with(['modules.contenus'])->findOrFail($formationId);
    $tousContenus   = $formation->modules->flatMap(fn($m) => $m->contenus);
    $total          = $tousContenus->count();
    $completesCount = \App\Models\ProgressionContenu::where('user_id', $user->id)
        ->whereIn('contenu_id', $tousContenus->pluck('id'))
        ->where('complete', true)
        ->count();
    $pctGlobal = $total > 0 ? round(($completesCount / $total) * 100) : 0;

    \App\Models\ProgressionFormation::updateOrCreate(
        ['user_id' => $user->id, 'formation_id' => $formationId],
        [
            'pourcentage_global' => $pctGlobal,
            'contenus_completes' => $completesCount,
            'complete'           => $pctGlobal >= 100,
            'termine_le'         => $pctGlobal >= 100 ? now() : null,
        ]
    );

    // ✅ Vérifier et attribuer les badges
    $badgeService   = app(\App\Services\BadgeService::class);
    $nouveauxBadges = $badgeService->verifierEtAttribuer($user->id, (int) $formationId);

    return response()->json([
        'message'        => 'Contenu marqué comme consulté',
        'complete'       => $pourcentage >= 100,
        'pourcentage'    => $pourcentage,
        'nouveaux_badges' => $nouveauxBadges,
    ]);
}

    // ─── Méthodes privées ─────────────────────────────────
    private function authorize_owner_or_admin(User $user, Formation $formation): void
    {
        if ($user->role === 'admin') return;
        if ($user->role === 'formateur' && $formation->formateur_id === $user->id) return;
        abort(403, 'Vous n\'êtes pas autorisé.');
    }

    private function formatContenu(Contenu $c, ?ProgressionContenu $progression = null): array
    {
        // URL d'accès : fichier uploadé ou URL externe
        $urlAcces = $c->url;
        if ($c->chemin_fichier) {
            $urlAcces = asset('storage/' . $c->chemin_fichier);
        }

        return [
            'id'         => (string) $c->id,
            'module_id'  => (string) $c->module_id,
            'titre'      => $c->titre,
            'type'       => $c->type,
            'url'        => $urlAcces,
            'duree'      => $c->duree,
            'resume'     => $c->resume,
            'miniature'  => $c->miniature,
            'ordre'      => $c->ordre,
            'a_fichier'  => !is_null($c->chemin_fichier),
            'progression' => $progression ? [
                'complete'    => $progression->complete,
                'pourcentage' => $progression->pourcentage,
                'consulte_le' => $progression->derniere_consultation?->toISOString(),
            ] : null,
        ];
    }
}
