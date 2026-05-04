<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificat;
use App\Models\Formation;
use App\Models\TentativeQuiz;
use App\Models\ProgressionFormation;
use Illuminate\Http\Request;

class CertificatController extends Controller
{
    // ── Lister les certificats de l'apprenant connecté ────────
    public function index(Request $request)
    {
        $user = $request->user();

        $certificats = Certificat::where('user_id', $user->id)
             ->with(['user', 'formation.formateur'])
            ->orderBy('emis_le', 'desc')
            ->get()
            ->map(fn($c) => $this->formatCertificat($c));

        return response()->json($certificats);
    }

    // ── Générer ou récupérer le certificat d'une formation ────
    public function generer(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::with(['modules.contenus', 'modules.quiz', 'formateur'])
            ->findOrFail($formationId);

        // Vérifier que la formation est complète (100%)
        $progression = ProgressionFormation::where('user_id', $user->id)
            ->where('formation_id', $formationId)
            ->first();

        if (!$progression || !$progression->complete) {
            return response()->json([
                'message' => 'Formation non complétée. Terminez tous les contenus pour obtenir le certificat.',
            ], 403);
        }

        // Vérifier qu'au moins un quiz a été réussi
        $quizIds = $formation->modules
    ->map(fn($m) => optional($m->quiz)->id)
    ->filter()
    ->values();

        $quizReussi = TentativeQuiz::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->where('reussi', true)
            ->exists();

        if (!$quizReussi) {
            return response()->json([
                'message' => 'Aucun quiz réussi. Réussissez au moins un quiz pour obtenir le certificat.',
            ], 403);
        }

        // Calculer la moyenne des meilleures tentatives par quiz
        $moyenne = $this->calculerMoyenne($user->id, $quizIds->toArray());
        $mention = $this->calculerMention($moyenne);

        // Créer ou récupérer le certificat existant
        $certificat = Certificat::firstOrCreate(
            ['user_id' => $user->id, 'formation_id' => $formationId],
            [
                'numero'   => 'CERT-' . strtoupper(uniqid()),
                'moyenne'  => $moyenne,
                'mention'  => $mention,
                'emis_le'  => now(),
            ]
        );

        // ✅ APRÈS avoir créé/récupéré le certificat, ajoute :
\App\Services\CodedFormationService::verifierApresObtentionCertificat(
    $user->id,
    (int) $formationId
);

       if ($certificat->wasRecentlyCreated) {
    // ✅ Notifier l'apprenant
    \App\Services\NotificationService::send(
        $user->id,
        "Certificat obtenu pour la formation \"{$formation->titre}\" — Mention : {$mention}",
        'certificat'
    );

    // ✅ Notifier les admins
    \App\Services\NotificationService::notifyAdmins(
        "Certificat généré pour {$user->prenom} {$user->nom} — Formation : \"{$formation->titre}\" (Mention : {$mention})",
        'info'
    );
}

        return response()->json($this->formatCertificat($certificat->load(['formation.formateur'])));
    }

    // ── Vérifier un certificat par son numéro (public) ────────
    public function verifier($numero)
    {
        $certificat = Certificat::where('numero', $numero)
            ->with(['user', 'formation.formateur'])
            ->first();

        if (!$certificat) {
            return response()->json(['valid' => false, 'message' => 'Certificat introuvable'], 404);
        }

        return response()->json([
            'valid'      => true,
            'certificat' => $this->formatCertificat($certificat),
        ]);
    }

    // ── Génération automatique appelée depuis QuizController ───
    public static function genererAutomatiquement(int $userId, int $formationId): ?\App\Models\Certificat
    {
        $formation = \App\Models\Formation::with(['modules.contenus', 'modules.quiz', 'formateur'])
            ->find($formationId);

        if (!$formation) return null;

        // Déjà certifié ?
        $existant = \App\Models\Certificat::where('user_id', $userId)
            ->where('formation_id', $formationId)
            ->first();
        if ($existant) return $existant;

        // Formation terminée (100% + tous quiz réussis) ?
        if (!\App\Services\CodedFormationService::estFormationTerminee($userId, $formation)) {
            return null;
        }

        // Calculer moyenne et mention
        $quizIds = $formation->modules
            ->map(fn($m) => optional($m->quiz)->id)
            ->filter()
            ->values();

        $moyenne = self::calculerMoyenneStatic($userId, $quizIds->toArray());
        $mention = self::calculerMentionStatic($moyenne);

        // Créer le certificat
        $certificat = \App\Models\Certificat::create([
            'user_id'      => $userId,
            'formation_id' => $formationId,
            'numero'       => 'CERT-' . strtoupper(uniqid()),
            'moyenne'      => $moyenne,
            'mention'      => $mention,
            'emis_le'      => now(),
        ]);

        // ✅ Notification apprenant
        \App\Services\NotificationService::send(
            $userId,
            "Certificat obtenu pour la formation \"{$formation->titre}\" — Mention : {$mention}",
            'certificat'
        );

        // ✅ Notification admins
        \App\Services\NotificationService::notifyAdmins(
            "📜 Certificat généré — Formation : \"{$formation->titre}\" (Mention : {$mention})",
            'info'
        );

        return $certificat;
    }

    // ── Helpers ───────────────────────────────────────────────
    private function calculerMoyenne(int $userId, array $quizIds): float
    {
        if (empty($quizIds)) return 0;

        $total = 0;
        $count = 0;

        foreach ($quizIds as $quizId) {
            $meilleure = TentativeQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->orderByDesc('score')
                ->first();

            if ($meilleure && $meilleure->score_max > 0) {
                $total += ($meilleure->score / $meilleure->score_max) * 100;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : 0;
    }

    private function calculerMention(float $moyenne): string
    {
        if ($moyenne >= 95) return 'Excellent';
        if ($moyenne >= 85) return 'Très Bien';
        if ($moyenne >= 70) return 'Bien';
        return 'Passable';
    }

    private function formatCertificat(Certificat $c): array
    {
        $formation = $c->formation;
        $formateur = $formation?->formateur;

        return [
            'id'              => $c->numero,
            'numero'          => $c->numero,
            'learnerName'     => $c->user?->prenom . ' ' . $c->user?->nom,
            'trainerName'     => $formateur
                                    ? $formateur->prenom . ' ' . $formateur->nom
                                    : 'Équipe pédagogique',
            'courseName'      => $formation?->titre ?? '',
            'formation_id'    => (string) $c->formation_id,
            'date'            => $c->emis_le?->toISOString() ?? now()->toISOString(),
            'averageScore'    => $c->moyenne,
            'mention'         => $c->mention,
            'quizScores'      => [],
        ];
    }

    private static function calculerMoyenneStatic(int $userId, array $quizIds): float
    {
        if (empty($quizIds)) return 0;

        $total = 0;
        $count = 0;

        foreach ($quizIds as $quizId) {
            $meilleure = \App\Models\TentativeQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->orderByDesc('score')
                ->first();

            if ($meilleure && $meilleure->score_max > 0) {
                $total += ($meilleure->score / $meilleure->score_max) * 100;
                $count++;
            }
        }

        return $count > 0 ? round($total / $count, 2) : 0;
    }

    private static function calculerMentionStatic(float $moyenne): string
    {
        if ($moyenne >= 95) return 'Excellent';
        if ($moyenne >= 85) return 'Très Bien';
        if ($moyenne >= 70) return 'Bien';
        return 'Passable';
    }
}