<?php
namespace App\Services;

use App\Models\Formation;
use App\Models\Certificat;
use App\Models\FormationAccesCode;
use App\Services\NotificationService;

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
        // Trouver toutes les formations codées qui ont cette formation comme prérequis
        $formationsCodées = Formation::where('is_coded', true)
            ->whereHas('prerequisFormations', fn($q) =>
                $q->where('prerequis_formation_id', $formationTermineeId)
            )
            ->with('prerequisFormations')
            ->get();

        foreach ($formationsCodées as $formationCodée) {
            // Déjà accès ?
            if (FormationAccesCode::where('user_id', $userId)
                    ->where('formation_id', $formationCodée->id)
                    ->exists()) {
                continue;
            }

            // Calculer les prérequis déjà obtenus
            $tousPrerequisIds = $formationCodée->prerequisFormations
                ->pluck('id')->toArray();

            $certificationObtenues = Certificat::where('user_id', $userId)
                ->whereIn('formation_id', $tousPrerequisIds)
                ->pluck('formation_id')
                ->toArray();

            $restants = array_diff($tousPrerequisIds, $certificationObtenues);

            if (empty($restants)) {
                // ✅ TOUS les prérequis sont complétés → envoyer le code
                NotificationService::send(
                    $userId,
                    "🎉 Félicitations ! Tu as débloqué le code d'accès à la formation codée \"{$formationCodée->titre}\" . \nCode : {$formationCodée->code}",
                    'info'
                );
            } else {
                // ⏳ Prérequis partiellement complétés → notifier ce qu'il reste
                $nomsRestants = Formation::whereIn('id', $restants)
                    ->pluck('titre')
                    ->toArray();
                $listeRestants = implode('" et "', $nomsRestants);

                NotificationService::send(
                    $userId,
                    "📚 Tu es près d'obtenir la formation codée \"{$formationCodée->titre}\" ! Il te reste seulement : \"{$listeRestants}\".",
                    'info'
                );
            }
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

    // Formation non codée → accès libre
    if (!$formation->is_coded) return true;

    // Admin → accès total
    if ($userRole === 'admin') return true;

    // Formateur propriétaire → accès direct
    if ($userRole === 'formateur' && $formation->formateur_id === $userId) return true;

    // Vérifier le code
    if ($formation->code !== strtoupper($codeSaisi)) return false;

    // Vérifier les prérequis
    $tousPrerequisIds = $formation->prerequisFormations->pluck('id')->toArray();
    if (!empty($tousPrerequisIds)) {
        $nbCerts = Certificat::where('user_id', $userId)
            ->whereIn('formation_id', $tousPrerequisIds)
            ->count();
        if ($nbCerts < count($tousPrerequisIds)) return false;
    }

    // Donner l'accès
    FormationAccesCode::firstOrCreate([
        'user_id'      => $userId,
        'formation_id' => $formationId,
    ]);

    return true;
}
}