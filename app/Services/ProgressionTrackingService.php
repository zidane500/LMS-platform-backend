<?php

namespace App\Services;

use App\Models\Formation;
use App\Models\ProgressionFormation;
use App\Models\ProgressionContenu;
use App\Models\TentativeQuiz;
use App\Models\Badge;
use App\Models\BadgeUtilisateur;

class ProgressionTrackingService
{
    public function refreshForUserAndFormation(int $userId, int $formationId): array
    {
        $formation = Formation::with(['modules.contenus', 'modules.quiz'])
            ->findOrFail($formationId);

        $tousContenus = $formation->modules->flatMap(fn($m) => $m->contenus);
        $totalContenus = $tousContenus->count();

        $progressions = ProgressionContenu::where('user_id', $userId)
            ->whereIn('contenu_id', $tousContenus->pluck('id'))
            ->get()
            ->keyBy('contenu_id');

        $contenusCompletes = $progressions->filter(fn($p) => $p->complete)->count();

        $modulesData = $formation->modules->map(function ($module) use ($progressions, $userId) {
            $contenus = $module->contenus;
            $total = $contenus->count();

            $completes = $contenus->filter(
                fn($c) => isset($progressions[$c->id]) && $progressions[$c->id]->complete
            )->count();

            $pourcentage = $total > 0 ? round(($completes / $total) * 100) : 0;

            $quizId = optional($module->quiz)->id;
            $derniereTentative = null;

            if ($quizId) {
                $derniereTentative = TentativeQuiz::where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->latest('created_at')
                    ->first();
            }

            return [
                'module_id' => (string) $module->id,
                'titre' => $module->titre,
                'total' => $total,
                'completes' => $completes,
                'pourcentage' => $pourcentage,
                'quiz' => $derniereTentative ? [
                    'quiz_id' => (string) $derniereTentative->quiz_id,
                    'score' => $derniereTentative->score,
                    'score_max' => $derniereTentative->score_max,
                    'pourcentage' => $derniereTentative->score_max > 0
                        ? round(($derniereTentative->score / $derniereTentative->score_max) * 100)
                        : 0,
                    'reussi' => $derniereTentative->reussi,
                    'termine_le' => $derniereTentative->termine_le?->toISOString(),
                ] : null,
            ];
        });

        $modulesCompletes = $modulesData->filter(fn($m) => $m['pourcentage'] >= 100)->count();

        $pourcentageGlobal = $totalContenus > 0
            ? round(($contenusCompletes / $totalContenus) * 100)
            : 0;

        ProgressionFormation::updateOrCreate(
            [
                'user_id' => $userId,
                'formation_id' => $formationId,
            ],
            [
                'pourcentage_global' => $pourcentageGlobal,
                'modules_completes' => $modulesCompletes,
                'contenus_completes' => $contenusCompletes,
                'complete' => $pourcentageGlobal >= 100,
                'termine_le' => $pourcentageGlobal >= 100 ? now() : null,
            ]
        );

        $quizIds = $formation->modules
            ->map(fn($m) => optional($m->quiz)->id)
            ->filter()
            ->values();

        $tentatives = TentativeQuiz::where('user_id', $userId)
            ->whereIn('quiz_id', $quizIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($t) => [
                'quiz_id' => (string) $t->quiz_id,
                'score' => $t->score,
                'score_max' => $t->score_max,
                'pourcentage' => $t->score_max > 0
                    ? round(($t->score / $t->score_max) * 100)
                    : 0,
                'reussi' => $t->reussi,
                'termine_le' => $t->termine_le?->toISOString(),
            ]);

        $nouveauxBadges = $this->attribuerBadges(
            $userId,
            $formationId,
            $pourcentageGlobal,
            $modulesCompletes,
            $tentatives
        );

        $badges = BadgeUtilisateur::where('user_id', $userId)
            ->where('formation_id', $formationId)
            ->with('badge')
            ->get()
            ->map(fn($b) => [
                'id' => (string) $b->badge->id,
                'code' => $b->badge->code,
                'nom' => $b->badge->nom,
                'description' => $b->badge->description,
                'icone' => $b->badge->icone,
                'obtenu_le' => $b->obtenu_le?->toISOString(),
            ]);

        return [
            'formation_id' => (string) $formationId,
            'pourcentage_global' => $pourcentageGlobal,
            'modules_completes' => $modulesCompletes,
            'contenus_completes' => $contenusCompletes,
            'total_contenus' => $totalContenus,
            'complete' => $pourcentageGlobal >= 100,
            'modules' => $modulesData->values(),
            'tentatives_quiz' => $tentatives->values(),
            'badges' => $badges->values(),
            'nouveaux_badges' => $nouveauxBadges,
        ];
    }

   private function attribuerBadges(
    int $userId,
    int $formationId,
    int $pourcentageGlobal,
    int $modulesCompletes,
    $tentatives
): array {
    $nouveauxBadges = [];

    if ($modulesCompletes >= 1) {
        $nouveau = $this->attribuerBadge(
            $userId,
            $formationId,
            'module_complete',
            'Module complété',
            'Vous avez complété un module à 100%',
            '📘'
        );
        if ($nouveau) {
            $nouveauxBadges[] = $nouveau;
        }
    }

    if ($pourcentageGlobal >= 100) {
        $nouveau = $this->attribuerBadge(
            $userId,
            $formationId,
            'formation_complete',
            'Formation terminée',
            'Vous avez complété la formation à 100%',
            '🏆'
        );
        if ($nouveau) {
            $nouveauxBadges[] = $nouveau;
        }
    }

    $quizPremierCoup = $tentatives
        ->groupBy('quiz_id')
        ->contains(function ($attempts) {
            $premiere = $attempts->sortBy('termine_le')->first();
            return $premiere && $premiere['reussi'] === true;
        });

    if ($quizPremierCoup) {
        $nouveau = $this->attribuerBadge(
            $userId,
            $formationId,
            'quiz_first_try',
            'Quiz réussi du premier coup',
            'Vous avez réussi un quiz dès la première tentative',
            '🎯'
        );
        if ($nouveau) {
            $nouveauxBadges[] = $nouveau;
        }
    }

    return $nouveauxBadges;
}

private function attribuerBadge(
    int $userId,
    int $formationId,
    string $code,
    string $nom,
    string $description,
    string $icone
): ?array {
    $badge = Badge::firstOrCreate(
        ['code' => $code],
        [
            'nom' => $nom,
            'description' => $description,
            'icone' => $icone,
        ]
    );

    $badgeUser = BadgeUtilisateur::firstOrCreate(
        [
            'user_id' => $userId,
            'formation_id' => $formationId,
            'badge_id' => $badge->id,
        ],
        [
            'obtenu_le' => now(),
        ]
    );

    if (!$badgeUser->wasRecentlyCreated) {
        return null;
    }

    return [
        'id' => (string) $badge->id,
        'code' => $badge->code,
        'nom' => $badge->nom,
        'description' => $badge->description,
        'icone' => $badge->icone,
        'obtenu_le' => $badgeUser->obtenu_le?->toISOString(),
    ];
}

}