<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\ProgressionFormation;
use App\Models\ProgressionContenu;
use App\Models\Badge;
use App\Models\BadgeUtilisateur;
use App\Models\TentativeQuiz;
use App\Models\Contenu;
use App\Models\ModuleFormation;
use Illuminate\Http\Request;

class ProgressionController extends Controller
{
    // ─── US 5.1 : Progression d'un apprenant sur une formation ──
    public function show(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::with(['modules.contenus'])->findOrFail($formationId);

        // Tous les contenus de la formation
        $tousContenus = $formation->modules->flatMap(fn($m) => $m->contenus);
        $totalContenus = $tousContenus->count();

        // Progressions de l'utilisateur pour cette formation
        $progressions = ProgressionContenu::where('user_id', $user->id)
            ->whereIn('contenu_id', $tousContenus->pluck('id'))
            ->get()
            ->keyBy('contenu_id');

        $contenusCompletes = $progressions->filter(fn($p) => $p->complete)->count();
        $pourcentage = $totalContenus > 0
            ? round(($contenusCompletes / $totalContenus) * 100)
            : 0;

        // Progression par module
        $modulesData = $formation->modules->map(function ($module) use ($progressions) {
            $contenus = $module->contenus;
            $total    = $contenus->count();
            $completes = $contenus->filter(
                fn($c) => isset($progressions[$c->id]) && $progressions[$c->id]->complete
            )->count();
            return [
                'module_id'   => (string) $module->id,
                'titre'       => $module->titre,
                'total'       => $total,
                'completes'   => $completes,
                'pourcentage' => $total > 0 ? round(($completes / $total) * 100) : 0,
            ];
        });

        // Quiz tentatives
        $quizIds = $formation->modules->flatMap(fn($m) => $m->quiz ?? [])->pluck('id');
        $tentatives = TentativeQuiz::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'quiz_id'        => (string) $t->quiz_id,
                'score'          => $t->score,
                'score_max'      => $t->score_max,
                'pourcentage'    => $t->score_max > 0
                    ? round(($t->score / $t->score_max) * 100) : 0,
                'reussi'         => $t->reussi,
                'termine_le'     => $t->termine_le?->toISOString(),
            ]);

        // Badges obtenus
        $badges = BadgeUtilisateur::where('user_id', $user->id)
            ->where('formation_id', $formationId)
            ->with('badge')
            ->get()
            ->map(fn($b) => [
                'id'          => (string) $b->badge->id,
                'code'        => $b->badge->code,
                'nom'         => $b->badge->nom,
                'description' => $b->badge->description,
                'icone'       => $b->badge->icone,
                'obtenu_le'   => $b->obtenu_le?->toISOString(),
            ]);

        // Mettre à jour progression_formations
        $progression = ProgressionFormation::updateOrCreate(
            ['user_id' => $user->id, 'formation_id' => $formationId],
            [
                'pourcentage_global' => $pourcentage,
                'contenus_completes' => $contenusCompletes,
                'complete'           => $pourcentage >= 100,
                'termine_le'         => $pourcentage >= 100 ? now() : null,
            ]
        );

        return response()->json([
            'formation_id'      => (string) $formationId,
            'pourcentage_global'=> $pourcentage,
            'contenus_completes'=> $contenusCompletes,
            'total_contenus'    => $totalContenus,
            'complete'          => $pourcentage >= 100,
            'modules'           => $modulesData,
            'tentatives_quiz'   => $tentatives,
            'badges'            => $badges,
        ]);
    }

    // ─── Progression de toutes les formations d'un apprenant ──
    public function index(Request $request)
    {
        $user = $request->user();

        $progressions = ProgressionFormation::where('user_id', $user->id)
            ->with('formation')
            ->get()
            ->map(fn($p) => [
                'formation_id'       => (string) $p->formation_id,
                'formation_titre'    => $p->formation->titre,
                'pourcentage_global' => $p->pourcentage_global,
                'complete'           => $p->complete,
                'termine_le'         => $p->termine_le?->toISOString(),
            ]);

        return response()->json($progressions);
    }

    // ─── US 5.2 : Progression vue formateur ───────────────────
    public function formateur(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::with(['modules.contenus'])->findOrFail($formationId);

        // Vérifier que c'est bien le formateur ou admin
        if ($user->role !== 'admin' && $formation->formateur_id !== $user->id) {
            abort(403);
        }

        $tousContenus  = $formation->modules->flatMap(fn($m) => $m->contenus);
        $totalContenus = $tousContenus->count();

        // Apprenants inscrits
        $inscrits = $formation->inscriptions()->with('user')->get();

        $apprenants = $inscrits->map(function ($inscription) use ($tousContenus, $totalContenus, $formationId) {
            $user         = $inscription->user;
            $progressions = ProgressionContenu::where('user_id', $user->id)
                ->whereIn('contenu_id', $tousContenus->pluck('id'))
                ->where('complete', true)
                ->count();

            $pourcentage = $totalContenus > 0
                ? round(($progressions / $totalContenus) * 100) : 0;

            return [
                'user_id'    => (string) $user->id,
                'prenom'     => $user->prenom,
                'nom'        => $user->nom,
                'email'      => $user->email,
                'pourcentage'=> $pourcentage,
                'complete'   => $pourcentage >= 100,
            ];
        });

        return response()->json([
            'formation_id'    => (string) $formationId,
            'titre'           => $formation->titre,
            'nb_inscrits'     => $inscrits->count(),
            'total_contenus'  => $totalContenus,
            'apprenants'      => $apprenants,
        ]);
    }
}