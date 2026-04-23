<?php
namespace App\Services;

use App\Models\Badge;
use App\Models\BadgeUtilisateur;
use App\Models\ProgressionContenu;
use App\Models\Formation;
use App\Models\TentativeQuiz;

class BadgeService
{
    // ─── Appelé après consultation d'un contenu ──────────────
    public function verifierEtAttribuer(int $userId, int $formationId): array
{
    $nouveauxBadges = [];

    $formation = Formation::with(['modules.contenus'])->find($formationId);
    if (!$formation) return [];

    $tousContenus = $formation->modules->flatMap(fn($m) => $m->contenus);
    $progressions = ProgressionContenu::where('user_id', $userId)
        ->whereIn('contenu_id', $tousContenus->pluck('id'))
        ->get();

    $nbCompletes = $progressions->where('complete', true)->count();

    // Badge : premier contenu
    if ($nbCompletes >= 1) {
        $b = $this->attribuer($userId, $formationId, 'premier_contenu');
        if ($b) $nouveauxBadges[] = $b;
    }

    // Badge : module complété
    foreach ($formation->modules as $module) {
        $contenusModule = $module->contenus;
        $total = $contenusModule->count();
        if ($total === 0) continue;
        $completes = $progressions
            ->whereIn('contenu_id', $contenusModule->pluck('id')->toArray())
            ->where('complete', true)->count();
        if ($completes >= $total) {
            $b = $this->attribuer($userId, $formationId, 'module_complete');
            if ($b) $nouveauxBadges[] = $b;
            break;
        }
    }

    // Badge : formation complète
    $totalContenus  = $tousContenus->count();
    $totalCompletes = $progressions->where('complete', true)->count();
    if ($totalContenus > 0 && $totalCompletes >= $totalContenus) {
        $b = $this->attribuer($userId, $formationId, 'formation_complete');
        if ($b) $nouveauxBadges[] = $b;
    }

    // Badge : assidu
    $aujourd_hui = now()->toDateString();
    $consultes_aujourd_hui = ProgressionContenu::where('user_id', $userId)
        ->whereDate('derniere_consultation', $aujourd_hui)
        ->where('complete', true)->count();
    if ($consultes_aujourd_hui >= 5) {
        $b = $this->attribuer($userId, $formationId, 'assidu');
        if ($b) $nouveauxBadges[] = $b;
    }

    // Badge : certifié (formation complète + quiz réussi)
    if ($totalContenus > 0 && $totalCompletes >= $totalContenus) {
        $quizReussi = TentativeQuiz::where('user_id', $userId)
            ->whereIn('quiz_id', function($q) use ($formationId) {
                $q->select('id')->from('quiz')
                  ->whereIn('module_id', function($q2) use ($formationId) {
                      $q2->select('id')->from('modules')->where('formation_id', $formationId);
                  });
            })
            ->where('reussi', true)->exists();
        if ($quizReussi) {
            $b = $this->attribuer($userId, $formationId, 'certifie');
            if ($b) $nouveauxBadges[] = $b;
        }
    }

    return array_values(array_filter($nouveauxBadges));
}

    // ─── Appelé après passage d'un quiz ──────────────────────
    public function verifierBadgeQuiz(
        int $userId,
        int $formationId,
        int $score,
        int $scoreMax,
        bool $reussi,
        bool $premiereTentative
    ): array {
        $nouveauxBadges = [];

        // ── Badge : quiz réussi ──────────────────────────────
        if ($reussi) {
            $b = $this->attribuer($userId, $formationId, 'quiz_reussi');
            if ($b) $nouveauxBadges[] = $b;
        }

        // ── Badge : score parfait premier coup ───────────────
        if ($premiereTentative && $scoreMax > 0 && $score >= $scoreMax) {
            $b = $this->attribuer($userId, $formationId, 'quiz_parfait');
            if ($b) $nouveauxBadges[] = $b;
        }

        // ── Badge : réussite premier coup ────────────────────
        if ($premiereTentative && $reussi) {
            $b = $this->attribuer($userId, $formationId, 'premier_coup');
            if ($b) $nouveauxBadges[] = $b;
        }

        return array_values(array_filter($nouveauxBadges));
    }

    // ─── Attribuer un badge si pas déjà obtenu ───────────────
    private function attribuer(int $userId, int $formationId, string $code): ?array
{
    $badge = Badge::where('code', $code)->first();
    if (!$badge) return null;

    // ✅ Déjà obtenu pour CETTE formation → ne rien faire
    $existePourFormation = BadgeUtilisateur::where('user_id', $userId)
        ->where('badge_id', $badge->id)
        ->where('formation_id', $formationId)
        ->exists();

    if ($existePourFormation) return null;

    // ✅ Première fois GLOBALEMENT (toutes formations confondues) → notifier
    $isFirstEver = !BadgeUtilisateur::where('user_id', $userId)
        ->where('badge_id', $badge->id)
        ->exists();

    BadgeUtilisateur::create([
        'user_id'      => $userId,
        'badge_id'     => $badge->id,
        'formation_id' => $formationId,
        'obtenu_le'    => now(),
    ]);

    // ✅ Notification DB uniquement la première fois toutes formations confondues
    if ($isFirstEver) {
        $nomBadge = $badge->nom;
        \App\Services\NotificationService::send(
            $userId,
            "Badge \"{$nomBadge}\" obtenu ! Félicitations !",
            'badge'
        );

        $badgesNotifiables = ['niveau_expert', 'temps_investi'];
        if (in_array($badge->code, $badgesNotifiables)) {
            $utilisateur = \App\Models\User::find($userId);
            \App\Services\NotificationService::notifyAdmins(
                "Badge \"{$nomBadge}\" attribué à {$utilisateur->prenom} {$utilisateur->nom}",
                'info'
            );
        }
    }

    return [
        'code'        => $badge->code,
        'nom'         => $badge->nom,
        'icone'       => $badge->icone,
        'description' => $badge->description,
    ];
}

    
}