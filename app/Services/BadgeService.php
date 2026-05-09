<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\BadgeUtilisateur;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\ProgressionContenu;
use App\Models\TentativeQuiz;

class BadgeService
{
    /**
     * Appelé après consultation d'un contenu.
     * Déclenche les badges liés aux contenus, modules, formations, niveaux et exploration.
     */
    public function verifierEtAttribuer(int $userId, int $formationId): array
    {
        $nouveauxBadges = [];

        $formation = Formation::with(['modules.contenus', 'modules.quiz'])->find($formationId);

        if (!$formation) {
            return [];
        }

        $tousContenus = $formation->modules->flatMap(fn ($m) => $m->contenus);
        $contenuIds = $tousContenus->pluck('id');

        $progressions = ProgressionContenu::where('user_id', $userId)
            ->whereIn('contenu_id', $contenuIds)
            ->get();

        $progressionsCompletes = $progressions->where('complete', true);
        $nbCompletes = $progressionsCompletes->count();

        // ─────────────────────────────────────
        // 1. Badges progression de base
        // ─────────────────────────────────────

        if ($nbCompletes >= 1) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'premier_contenu');
        }

        $modulesCompletes = 0;

        foreach ($formation->modules as $module) {
            $contenusModule = $module->contenus;
            $totalModule = $contenusModule->count();

            if ($totalModule === 0) {
                continue;
            }

            $completesModule = $progressionsCompletes
                ->whereIn('contenu_id', $contenusModule->pluck('id')->toArray())
                ->count();

            if ($completesModule >= $totalModule) {
                $modulesCompletes++;
            }
        }

        if ($modulesCompletes >= 1) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'module_complete');
        }

        if ($modulesCompletes >= 5) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'apprenant_actif');
        }

        $totalContenus = $tousContenus->count();
        $formationComplete = $totalContenus > 0 && $nbCompletes >= $totalContenus;

        if ($formationComplete) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'formation_complete');
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'objectif_atteint');
        }

        // ─────────────────────────────────────
        // 2. Badge assidu : 5 contenus complétés aujourd'hui
        // ─────────────────────────────────────

        $consultesAujourdhui = ProgressionContenu::where('user_id', $userId)
            ->whereDate('derniere_consultation', now()->toDateString())
            ->where('complete', true)
            ->count();

        if ($consultesAujourdhui >= 5) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'assidu');
        }

        // ─────────────────────────────────────
        // 3. Badges contenus
        // ─────────────────────────────────────

        $contenusCompletesIds = $progressionsCompletes->pluck('contenu_id');

        $contenusCompletes = $tousContenus
            ->whereIn('id', $contenusCompletesIds)
            ->values();

        $typesCompletes = $contenusCompletes
            ->pluck('type')
            ->filter()
            ->map(fn ($type) => strtolower((string) $type))
            ->unique()
            ->values()
            ->toArray();

        $nbVideos = $contenusCompletes
            ->filter(fn ($c) => strtolower((string) $c->type) === 'video')
            ->count();

        $nbPdf = $contenusCompletes
            ->filter(fn ($c) => strtolower((string) $c->type) === 'pdf')
            ->count();

        if ($nbVideos >= 10) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'amateur_videos');
        }

        if ($nbPdf >= 5) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'lecteur_assidu');
        }

        $typesRequis = ['video', 'pdf', 'audio', 'scorm'];

        if (empty(array_diff($typesRequis, $typesCompletes))) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'explorateur_media');
        }

        // ─────────────────────────────────────
        // 4. Badges niveaux — tous parcours confondus
        // ─────────────────────────────────────

        $modulesCompletesGlobal = $this->countModulesCompletesGlobal($userId);

        if ($modulesCompletesGlobal >= 5) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'niveau_bronze');
        }

        if ($modulesCompletesGlobal >= 15) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'niveau_argent');
        }

        if ($modulesCompletesGlobal >= 30) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'niveau_or');
        }

        if ($modulesCompletesGlobal >= 50) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'niveau_expert');
        }

        // ─────────────────────────────────────
        // 5. Badges exploration
        // ─────────────────────────────────────

        $formationsConsulteesIds = $this->formationsConsulteesIds($userId);

        if (count($formationsConsulteesIds) >= 5) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'explorateur');
        }

        $categoriesDistinctes = Formation::whereIn('id', $formationsConsulteesIds)
            ->pluck('categorie')
            ->filter()
            ->unique()
            ->count();

        if ($categoriesDistinctes >= 3) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'curieux');
        }

        // Early adopter : parmi les 100 premiers comptes
        if ($userId <= 100) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'early_adopter');
        }

        // ─────────────────────────────────────
        // 6. Badge certifié : formation complète + quiz réussi
        // ─────────────────────────────────────

        if ($formationComplete) {
            $quizIds = $formation->modules
                ->map(fn ($module) => optional($module->quiz)->id)
                ->filter()
                ->values();

            $quizReussi = $quizIds->isNotEmpty()
                ? TentativeQuiz::where('user_id', $userId)
                    ->whereIn('quiz_id', $quizIds)
                    ->where('reussi', true)
                    ->exists()
                : false;

            if ($quizReussi) {
                $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'certifie');
            }
        }

        return array_values(array_filter($nouveauxBadges));
    }

    /**
     * Appelé après passage d'un quiz.
     */
    public function verifierBadgeQuiz(
        int $userId,
        int $formationId,
        int $score,
        int $scoreMax,
        bool $reussi,
        bool $premiereTentative,
        ?int $quizId = null
    ): array {
        $nouveauxBadges = [];

        $pourcentage = $scoreMax > 0 ? round(($score / $scoreMax) * 100) : 0;

        if ($reussi) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'quiz_reussi');
        }

        if ($premiereTentative && $scoreMax > 0 && $score >= $scoreMax) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'quiz_parfait');
        }

        if ($premiereTentative && $reussi) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'premier_coup');
        }

        if ($pourcentage >= 95) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'mention_excellent');
        } elseif ($pourcentage >= 85) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'mention_tres_bien');
        } elseif ($pourcentage >= 70) {
            $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'mention_bien');
        }

        if ($quizId) {
            $tentatives = TentativeQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->orderBy('created_at')
                ->get();

            if ($tentatives->count() > 3) {
                $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'perseverant');
            }

            if ($tentatives->count() >= 2) {
                $last = $tentatives->last();
                $previous = $tentatives[$tentatives->count() - 2];

                if ($last && $previous && $last->score > $previous->score) {
                    $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'en_amelioration');
                }
            }

            $bestOtherScore = TentativeQuiz::where('quiz_id', $quizId)
                ->where('user_id', '<>', $userId)
                ->max('score');

            if ($bestOtherScore !== null && $score > $bestOtherScore) {
                $this->ajouterBadge($nouveauxBadges, $userId, $formationId, 'top_score');
            }
        }

        // Après un quiz réussi, on revérifie aussi les badges formation/certification.
        $apresQuiz = $this->verifierEtAttribuer($userId, $formationId);

        return array_values(array_filter(array_merge($nouveauxBadges, $apresQuiz)));
    }

    /**
     * Ajouter un badge à la liste si attribution réussie.
     */
    private function ajouterBadge(array &$nouveauxBadges, int $userId, int $formationId, string $code): void
    {
        $badge = $this->attribuer($userId, $formationId, $code);

        if ($badge) {
            $nouveauxBadges[] = $badge;
        }
    }

    /**
     * Attribuer un badge si l'utilisateur ne l'a pas déjà obtenu pour cette formation.
     */
    private function attribuer(int $userId, int $formationId, string $code): ?array
    {
        $badge = Badge::where('code', $code)->first();

        if (!$badge) {
            return null;
        }

        $existePourFormation = BadgeUtilisateur::where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->where('formation_id', $formationId)
            ->exists();

        if ($existePourFormation) {
            return null;
        }

        $isFirstEver = !BadgeUtilisateur::where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->exists();

        BadgeUtilisateur::create([
            'user_id'      => $userId,
            'badge_id'     => $badge->id,
            'formation_id' => $formationId,
            'obtenu_le'    => now(),
        ]);

        if ($isFirstEver) {
            \App\Services\NotificationService::send(
                $userId,
                "Badge \"{$badge->nom}\" obtenu ! Félicitations !",
                'badge'
            );

            $badgesNotifiables = ['niveau_expert', 'temps_investi'];

            if (in_array($badge->code, $badgesNotifiables, true)) {
                $utilisateur = \App\Models\User::find($userId);

                if ($utilisateur) {
                    \App\Services\NotificationService::notifyAdmins(
                        "Badge \"{$badge->nom}\" attribué à {$utilisateur->prenom} {$utilisateur->nom}",
                        'info'
                    );
                }
            }
        }

        return [
            'code'        => $badge->code,
            'nom'         => $badge->nom,
            'icone'       => $badge->icone,
            'description' => $badge->description,
        ];
    }

    /**
     * Compter les modules complétés dans toutes les formations de l'utilisateur.
     */
    private function countModulesCompletesGlobal(int $userId): int
    {
        $formations = Formation::with(['modules.contenus'])
            ->whereHas('inscriptions', fn ($q) => $q->where('user_id', $userId))
            ->get();

        $count = 0;

        foreach ($formations as $formation) {
            foreach ($formation->modules as $module) {
                $contenus = $module->contenus;
                $total = $contenus->count();

                if ($total === 0) {
                    continue;
                }

                $done = ProgressionContenu::where('user_id', $userId)
                    ->whereIn('contenu_id', $contenus->pluck('id'))
                    ->where('complete', true)
                    ->count();

                if ($done >= $total) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Formations dans lesquelles l'utilisateur a consulté au moins un contenu.
     */
    private function formationsConsulteesIds(int $userId): array
    {
        return ProgressionContenu::where('user_id', $userId)
            ->where('complete', true)
            ->with('contenu.module')
            ->get()
            ->pluck('contenu.module.formation_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}