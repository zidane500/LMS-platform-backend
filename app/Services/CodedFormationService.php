<?php
namespace App\Services;

use App\Models\Formation;
use App\Models\Certificat;
use App\Models\FormationAccesCode;
use Illuminate\Support\Facades\Log;

class CodedFormationService
{
    // ─────────────────────────────────────────────────────────
    // Appelé après génération d'un certificat (inchangé).
    // Garde uniquement la notification partielle si applicable.
    // ─────────────────────────────────────────────────────────
    public static function verifierApresObtentionCertificat(
        int $userId,
        int $formationTermineeId
    ): void {
        // Le certificat n'est plus le déclencheur principal.
        // Cette méthode est conservée pour compatibilité mais ne fait rien.
        // Le déclencheur est maintenant verifierApresQuizReussi().
    }

    // ─────────────────────────────────────────────────────────
    // ✅ Fix 1 — Appelé après chaque quiz réussi.
    // Vérifie si la formation est maintenant "terminée"
    // (100 % contenus + tous quiz réussis) et envoie
    // la notification appropriée pour les formations codées.
    // ─────────────────────────────────────────────────────────
    public static function verifierApresQuizReussi(
        int $userId,
        int $formationId
    ): void {
        try {
            $formation = Formation::with(['modules.contenus', 'modules.quiz'])
                ->find($formationId);
            if (!$formation) return;

            // 1. La formation est-elle terminée (100 % + tous quiz réussis) ?
            if (!self::estFormationTerminee($userId, $formation)) return;

            // 2. Chercher toutes les formations codées qui requirent cette formation
            $formationsCodees = $formation
                ->formationsCodeesQuiRequerent()
                ->where('is_coded', true)
                ->with('prerequisFormations')
                ->get();

            foreach ($formationsCodees as $formationCodee) {
                // Déjà accès donné → skip
                if (FormationAccesCode::where('user_id', $userId)
                        ->where('formation_id', $formationCodee->id)
                        ->exists()) {
                    continue;
                }

                $tousPrerequisIds = $formationCodee->prerequisFormations
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();

                if (empty($tousPrerequisIds)) continue;

                // 3. Vérifier quels prérequis sont terminés
                $termines  = [];
                $restants  = [];

                foreach ($tousPrerequisIds as $prereqId) {
                    $prereqFormation = Formation::with(['modules.contenus', 'modules.quiz'])
                        ->find($prereqId);

                    if ($prereqFormation && self::estFormationTerminee($userId, $prereqFormation)) {
                        $termines[] = $prereqId;
                    } else {
                        $titre = Formation::find($prereqId)?->titre ?? "Formation #{$prereqId}";
                        $restants[] = $titre;
                    }
                }

                if (empty($restants)) {
                    // ✅ TOUS les prérequis sont terminés → ENVOYER SEULEMENT la notification
                    // ❌ SUPPRIMÉ : FormationAccesCode::firstOrCreate(...) 
                    // L'utilisateur DOIT saisir le code manuellement

                    NotificationService::send(
                        $userId,
                        "🎉 Félicitations ! Tous les prérequis de \"{$formationCodee->titre}\" sont complétés !\n🔑 Code d'accès : {$formationCodee->code}\n\nRendez-vous sur la formation et entrez ce code pour y accéder.",
                        'info'
                    );
                } else {
                    // ⏳ Prérequis partiellement terminés → notifier ce qu'il reste
                    $liste = implode('" et "', $restants);
                    NotificationService::send(
                        $userId,
                        "📚 Tu es près d'obtenir \"{$formationCodee->titre}\" ! Il te reste : \"{$liste}\".",
                        'info'
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::error('verifierApresQuizReussi error: ' . $e->getMessage(), [
                'userId'      => $userId,
                'formationId' => $formationId,
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Helper : formation terminée = 100 % contenus + tous quiz réussis
    // ─────────────────────────────────────────────────────────
    public static function estFormationTerminee(int $userId, Formation $formation): bool
    {
        // Progression globale à 100 %
        $progression = \App\Models\ProgressionFormation::where('user_id', $userId)
            ->where('formation_id', $formation->id)
            ->first();

        if (!$progression || $progression->pourcentage_global < 100) return false;

        // Tous les quiz réussis
        $quizIds = $formation->modules
            ->map(fn($m) => optional($m->quiz)->id)
            ->filter()
            ->values();

        foreach ($quizIds as $quizId) {
            $aReussi = \App\Models\TentativeQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->where('reussi', true)
                ->exists();
            if (!$aReussi) return false;
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Vérifier le code saisi — c'est ICI que l'accès est accordé.
    // ─────────────────────────────────────────────────────────
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

        // Vérifier que tous les prérequis sont terminés
        $tousPrerequisIds = $formation->prerequisFormations->pluck('id')->toArray();
        if (!empty($tousPrerequisIds)) {
            foreach ($tousPrerequisIds as $prereqId) {
                $prereqFormation = Formation::with(['modules.contenus', 'modules.quiz'])
                    ->find($prereqId);
                if (!$prereqFormation || !self::estFormationTerminee($userId, $prereqFormation)) {
                    return false;
                }
            }
        }

        // ✅ Code correct + prérequis OK → créer l'accès SEULEMENT ICI
        FormationAccesCode::firstOrCreate(
            ['user_id' => $userId, 'formation_id' => $formationId],
            ['accessed_at' => now()]
        );

        return true;
    }
}