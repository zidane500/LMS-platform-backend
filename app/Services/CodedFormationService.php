<?php
namespace App\Services;

use App\Models\Formation;
use App\Models\Certificat;
use App\Models\FormationAccesCode;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class CodedFormationService
{
    /**
     * Appelé après qu'un user obtient un certificat.
     * Vérifie les formations codées concernées et notifie.
     */
    public static function verifierApresObtentionCertificat(
        int $userId,
        int $formationTermineeId
    ): void {
        try {
            // ✅ FIX 1 — Utiliser la relation inverse directement
            // pour éviter l'ambiguïté SQL avec whereHas + même table
            $formationTerminee = Formation::find($formationTermineeId);
            if (!$formationTerminee) return;

            // Toutes les formations codées qui REQUIÈRENT cette formation comme prérequis
            $formationsCodees = $formationTerminee
                ->formationsCodeesQuiRequerent()
                ->where('is_coded', true)
                ->with('prerequisFormations')
                ->get();

            foreach ($formationsCodees as $formationCodee) {
                // Déjà accès ? → Ne pas renvoyer la notification
                if (FormationAccesCode::where('user_id', $userId)
                        ->where('formation_id', $formationCodee->id)
                        ->exists()) {
                    continue;
                }

                // Calculer les prérequis déjà obtenus
                $tousPrerequisIds = $formationCodee->prerequisFormations
                    ->pluck('id')->toArray();

                if (empty($tousPrerequisIds)) continue;

                $certificationsObtenues = Certificat::where('user_id', $userId)
                    ->whereIn('formation_id', $tousPrerequisIds)
                    ->pluck('formation_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();

                $tousPrerequisIdsInt = array_map('intval', $tousPrerequisIds);
                $restants = array_diff($tousPrerequisIdsInt, $certificationsObtenues);

                if (empty($restants)) {
                    // ✅ FIX 2 — firstOrCreate avec bons paramètres (search / defaults séparés)
                    FormationAccesCode::firstOrCreate(
                        [
                            'user_id'      => $userId,
                            'formation_id' => $formationCodee->id,
                        ],
                        [
                            'accessed_at' => now(),
                        ]
                    );

                    // ✅ Envoyer le code dans la notification
                    NotificationService::send(
                        $userId,
                        "🎉 Félicitations ! Tu as débloqué le code d'accès à la formation codée \"{$formationCodee->titre}\".\n🔑 Code : {$formationCodee->code}",
                        'info'
                    );
                } else {
                    // ⏳ Prérequis partiellement complétés
                    $nomsRestants = Formation::whereIn('id', $restants)
                        ->pluck('titre')
                        ->toArray();
                    $listeRestants = implode('" et "', $nomsRestants);

                    NotificationService::send(
                        $userId,
                        "📚 Tu es près d'obtenir la formation codée \"{$formationCodee->titre}\" ! Il te reste seulement : \"{$listeRestants}\".",
                        'info'
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('CodedFormationService error: ' . $e->getMessage(), [
                'userId'              => $userId,
                'formationTermineeId' => $formationTermineeId,
                'trace'               => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Vérifier si le code saisi par l'utilisateur est correct.
     */
    public static function verifierEtDonnerAcces(
        int    $userId,
        int    $formationId,
        string $codeSaisi,
        string $userRole = 'apprenant'
    ): bool {
        $formation = Formation::with('prerequisFormations')->findOrFail($formationId);

        if (!$formation->is_coded) return true;
        if ($userRole === 'admin') return true;
        if ($userRole === 'formateur' && $formation->formateur_id === $userId) return true;

        // Vérifier le code (insensible à la casse)
        if (strtoupper($formation->code) !== strtoupper($codeSaisi)) return false;

        // Vérifier les prérequis
        $tousPrerequisIds = $formation->prerequisFormations->pluck('id')->toArray();
        if (!empty($tousPrerequisIds)) {
            $nbCerts = Certificat::where('user_id', $userId)
                ->whereIn('formation_id', $tousPrerequisIds)
                ->count();
            if ($nbCerts < count($tousPrerequisIds)) return false;
        }

        // Donner l'accès
        FormationAccesCode::firstOrCreate(
            ['user_id' => $userId, 'formation_id' => $formationId],
            ['accessed_at' => now()]
        );

        return true;
    }

    public static function verifierApresQuizReussi(
    int $userId,
    int $formationId
): void {
    try {
        $formation = Formation::with(['modules.contenus', 'modules.quiz'])->find($formationId);
        if (!$formation) return;

        // 1. Vérifier que la progression globale est à 100%
        $progression = \App\Models\ProgressionFormation::where('user_id', $userId)
            ->where('formation_id', $formationId)
            ->first();

        if (!$progression || $progression->pourcentage_global < 100) return;

        // 2. Vérifier que tous les quiz sont réussis
        $quizIds = $formation->modules
            ->map(fn($m) => optional($m->quiz)->id)
            ->filter()
            ->values();

        foreach ($quizIds as $quizId) {
            $aReussi = \App\Models\TentativeQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->where('reussi', true)
                ->exists();
            if (!$aReussi) return; // Un quiz non réussi → formation pas terminée
        }

        // ✅ Formation terminée (100% + tous quiz réussis)
        // Chercher les formations codées qui utilisent CETTE formation comme prérequis
        $formationsCodees = $formation
            ->formationsCodeesQuiRequerent()
            ->where('is_coded', true)
            ->with('prerequisFormations')
            ->get();

        foreach ($formationsCodees as $formationCodee) {
            // Déjà accès ? → Skip
            if (\App\Models\FormationAccesCode::where('user_id', $userId)
                    ->where('formation_id', $formationCodee->id)
                    ->exists()) {
                continue;
            }

            $tousPrerequisIds = $formationCodee->prerequisFormations
                ->pluck('id')->map(fn($id) => (int) $id)->toArray();

            if (empty($tousPrerequisIds)) continue;

            // Vérifier chaque prérequis : 100% + tous quiz réussis
            $tousTermines = true;
            foreach ($tousPrerequisIds as $prereqId) {
                $progPrereq = \App\Models\ProgressionFormation::where('user_id', $userId)
                    ->where('formation_id', $prereqId)
                    ->first();

                if (!$progPrereq || $progPrereq->pourcentage_global < 100) {
                    $tousTermines = false;
                    break;
                }

                // Vérifier les quiz du prérequis
                $prereqFormation = Formation::with('modules.quiz')->find($prereqId);
                if (!$prereqFormation) { $tousTermines = false; break; }

                $prereqQuizIds = $prereqFormation->modules
                    ->map(fn($m) => optional($m->quiz)->id)->filter()->values();

                foreach ($prereqQuizIds as $qId) {
                    $ok = \App\Models\TentativeQuiz::where('user_id', $userId)
                        ->where('quiz_id', $qId)->where('reussi', true)->exists();
                    if (!$ok) { $tousTermines = false; break 2; }
                }
            }

            if ($tousTermines) {
                // ✅ Tous les prérequis sont terminés → envoyer le code
                \App\Models\FormationAccesCode::firstOrCreate(
                    ['user_id' => $userId, 'formation_id' => $formationCodee->id],
                    ['accessed_at' => now()]
                );

                NotificationService::send(
                    $userId,
                    "🎉 Félicitations ! Tu as débloqué le code d'accès à la formation codée \"{$formationCodee->titre}\".\n🔑 Code : {$formationCodee->code}",
                    'info'
                );
            } else {
                // ⏳ Partiellement terminé
                $restants = [];
                foreach ($tousPrerequisIds as $prereqId) {
                    $progPrereq = \App\Models\ProgressionFormation::where('user_id', $userId)
                        ->where('formation_id', $prereqId)->first();
                    if (!$progPrereq || $progPrereq->pourcentage_global < 100) {
                        $titre = Formation::find($prereqId)?->titre ?? "Formation #{$prereqId}";
                        $restants[] = $titre;
                    }
                }
                if (!empty($restants)) {
                    $liste = implode('" et "', $restants);
                    NotificationService::send(
                        $userId,
                        "📚 Tu es près d'obtenir \"{$formationCodee->titre}\" ! Il te reste : \"{$liste}\".",
                        'info'
                    );
                }
            }
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('verifierApresQuizReussi error: ' . $e->getMessage());
    }
}
}