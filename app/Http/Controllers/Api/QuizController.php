<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\ChoixReponse;
use App\Models\TentativeQuiz;
use App\Models\ReponseApprenant;
use App\Models\Formation;
use App\Services\GlmCorrectionService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function show(Request $request, $formationId, $moduleId)
    {
        $user = auth('sanctum')->user();

        $quiz = Quiz::with(['questions.choix'])
            ->where('module_id', $moduleId)
            ->first();

        if (!$quiz) {
            return response()->json(['message' => 'Aucun quiz pour ce module'], 404);
        }

        $nbTentatives = 0;
        $meilleureNote = null;

        if ($user) {
            $tentatives = TentativeQuiz::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->orderBy('score', 'desc')
                ->get();

            $nbTentatives = $tentatives->count();
            $meilleureNote = $tentatives->first()?->score;
        }

        return response()->json([
            ...$this->formatQuiz($quiz, $user?->role),
            'nb_tentatives' => $nbTentatives,
            'meilleure_note' => $meilleureNote,
            'peut_repasser' => $nbTentatives < $quiz->nb_tentatives_max,
        ]);
    }

    public function store(Request $request, $formationId, $moduleId)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($formationId);
        $this->authorize_owner_or_admin($user, $formation);

        if (Quiz::where('module_id', $moduleId)->exists()) {
            return response()->json(['message' => 'Ce module a déjà un quiz.'], 409);
        }

        $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'seuil_reussite' => 'nullable|integer|min:0|max:100',
            'duree_minutes' => 'nullable|integer|min:1',
            'nb_tentatives_max' => 'nullable|integer|min:1|max:10',
            'questions' => 'required|array|min:1',
            'questions.*.texte' => 'required|string',
            'questions.*.type' => 'required|in:qcm,vrai_faux,texte_libre',
            'questions.*.points' => 'nullable|integer|min:1',
            'questions.*.correction_attendue' => 'nullable|string',
            'questions.*.choix' => 'nullable|array',
            'questions.*.choix.*.texte' => 'required|string',
            'questions.*.choix.*.est_correct' => 'required|boolean',
        ]);

        $quiz = Quiz::create([
            'module_id' => $moduleId,
            'titre' => $request->titre,
            'description' => $request->description,
            'seuil_reussite' => $request->seuil_reussite ?? 70,
            'duree_minutes' => $request->duree_minutes,
            'nb_tentatives_max' => $request->nb_tentatives_max ?? 3,
            'statut' => 'actif',
        ]);

        foreach ($request->questions as $i => $qData) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'texte' => $qData['texte'],
                'type' => $qData['type'],
                'points' => $qData['points'] ?? 1,
                'ordre' => $i + 1,
                'correction_attendue' => $qData['type'] === 'texte_libre'
                    ? ($qData['correction_attendue'] ?? null)
                    : null,
            ]);

            if (!empty($qData['choix'])) {
                foreach ($qData['choix'] as $j => $cData) {
                    ChoixReponse::create([
                        'question_id' => $question->id,
                        'texte' => $cData['texte'],
                        'est_correct' => $cData['est_correct'],
                        'ordre' => $j + 1,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Quiz créé avec succès',
            'quiz' => $this->formatQuiz($quiz->load('questions.choix'), $user->role),
        ], 201);
    }

    public function update(Request $request, $formationId, $moduleId, $quizId)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($formationId);
        $quiz = Quiz::findOrFail($quizId);

        $this->authorize_owner_or_admin($user, $formation);

        $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'seuil_reussite' => 'nullable|integer|min:0|max:100',
            'duree_minutes' => 'nullable|integer|min:1',
            'nb_tentatives_max' => 'nullable|integer|min:1|max:10',
            'questions' => 'sometimes|array|min:1',
            'questions.*.texte' => 'required|string',
            'questions.*.type' => 'required|in:qcm,vrai_faux,texte_libre',
            'questions.*.points' => 'nullable|integer|min:1',
            'questions.*.correction_attendue' => 'nullable|string',
            'questions.*.choix' => 'nullable|array',
            'questions.*.choix.*.texte' => 'required|string',
            'questions.*.choix.*.est_correct' => 'required|boolean',
        ]);

        $quiz->update($request->only([
            'titre',
            'description',
            'seuil_reussite',
            'duree_minutes',
            'nb_tentatives_max',
        ]));

        if ($request->has('questions')) {
            $quiz->questions()->delete();

            foreach ($request->questions as $i => $qData) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'texte' => $qData['texte'],
                    'type' => $qData['type'],
                    'points' => $qData['points'] ?? 1,
                    'ordre' => $i + 1,
                    'correction_attendue' => $qData['type'] === 'texte_libre'
                        ? ($qData['correction_attendue'] ?? null)
                        : null,
                ]);

                if (!empty($qData['choix'])) {
                    foreach ($qData['choix'] as $j => $cData) {
                        ChoixReponse::create([
                            'question_id' => $question->id,
                            'texte' => $cData['texte'],
                            'est_correct' => $cData['est_correct'],
                            'ordre' => $j + 1,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Quiz mis à jour',
            'quiz' => $this->formatQuiz($quiz->load('questions.choix'), $user->role),
        ]);
    }

    public function destroy(Request $request, $formationId, $moduleId, $quizId)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($formationId);
        $quiz = Quiz::findOrFail($quizId);

        $this->authorize_owner_or_admin($user, $formation);

        $quiz->delete();

        return response()->json(['message' => 'Quiz supprimé avec succès']);
    }

    public function passer(Request $request, $formationId, $moduleId, $quizId)
    {
        $user = $request->user();
        $quiz = Quiz::with(['questions.choix', 'module.formation'])->findOrFail($quizId);
        $correctionIA = app(GlmCorrectionService::class);

        $nbTentatives = TentativeQuiz::where('quiz_id', $quizId)
            ->where('user_id', $user->id)
            ->count();

        if ($nbTentatives >= $quiz->nb_tentatives_max) {
            return response()->json([
                'message' => "Limite de {$quiz->nb_tentatives_max} tentatives atteinte.",
            ], 403);
        }

        $request->validate([
            'reponses' => 'required|array',
            'reponses.*.question_id' => 'required|integer',
            'reponses.*.choix_id' => 'nullable|integer',
            'reponses.*.reponse_texte' => 'nullable|string',
            'duree_secondes' => 'nullable|integer',
        ]);

        $scoreTotal = 0;
        $scoreMax = 0;
        $reponsesData = [];

        foreach ($quiz->questions as $question) {
            $scoreMax += $question->points;

            $reponse = collect($request->reponses)
                ->firstWhere('question_id', $question->id);

            if (!$reponse) {
                continue;
            }

            $estCorrect = false;
            $pointsObtenus = 0;
            $scoreIA = null;
            $feedbackIA = null;
            $pointsForts = null;
            $pointsAmelioration = null;
            $reponseTexte = $reponse['reponse_texte'] ?? null;

            if ($question->type === 'qcm' || $question->type === 'vrai_faux') {
                if (!empty($reponse['choix_id'])) {
                    $choix = $question->choix->firstWhere('id', $reponse['choix_id']);
                    $estCorrect = $choix?->est_correct ?? false;
                    $pointsObtenus = $estCorrect ? $question->points : 0;
                }
            } elseif ($question->type === 'texte_libre') {
                $texte = trim((string) ($reponse['reponse_texte'] ?? ''));

                if ($texte !== '') {
                    $resultatIA = $correctionIA->corrigerReponseLibre(
                        $question->texte,
                        $texte,
                        $question->correction_attendue,
                        "Formation : " . ($quiz->module->formation->titre ?? ''),
                        $question->points
                    );

                    $scoreIA = (int) ($resultatIA['score'] ?? 0);
                    $estCorrect = (bool) ($resultatIA['est_correct'] ?? false);
                    $feedbackIA = $resultatIA['feedback'] ?? null;
                    $pointsForts = $resultatIA['points_forts'] ?? null;
                    $pointsAmelioration = $resultatIA['points_amelioration'] ?? null;
                    $pointsObtenus = (int) round(($scoreIA / 100) * $question->points);
                }
            }

            $scoreTotal += $pointsObtenus;

            $reponsesData[] = [
                'question_id' => $question->id,
                'choix_id' => $reponse['choix_id'] ?? null,
                'reponse_texte' => $reponseTexte,
                'est_correct' => $estCorrect,
                'score_ia' => $scoreIA,
                'feedback_ia' => $feedbackIA,
                'points_forts' => $pointsForts,
                'points_amelioration' => $pointsAmelioration,
                'points_obtenus' => $pointsObtenus,
            ];
        }

        $pourcentage = $scoreMax > 0 ? round(($scoreTotal / $scoreMax) * 100) : 0;
        $reussi = $pourcentage >= $quiz->seuil_reussite;

        $tentative = TentativeQuiz::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => $scoreTotal,
            'score_max' => $scoreMax,
            'reussi' => $reussi,
            'duree_secondes' => $request->duree_secondes,
            'termine_le' => now(),
        ]);

        foreach ($reponsesData as $rd) {
            ReponseApprenant::create([
                'tentative_id' => $tentative->id,
                'question_id' => $rd['question_id'],
                'choix_id' => $rd['choix_id'],
                'reponse_texte' => $rd['reponse_texte'],
                'est_correct' => $rd['est_correct'],
                'score_ia' => $rd['score_ia'],
                'feedback_ia' => $rd['feedback_ia'],
                'points_forts' => $rd['points_forts'],
                'points_amelioration' => $rd['points_amelioration'],
                'points_obtenus' => $rd['points_obtenus'],
            ]);
        }

        return response()->json([
            'score' => $scoreTotal,
            'score_max' => $scoreMax,
            'pourcentage' => $pourcentage,
            'reussi' => $reussi,
            'seuil_reussite' => $quiz->seuil_reussite,
            'tentative_id' => $tentative->id,
            'nb_tentatives' => $nbTentatives + 1,
            'peut_repasser' => ($nbTentatives + 1) < $quiz->nb_tentatives_max,
            'corrections' => $quiz->questions->map(function ($q) use ($reponsesData) {
                $rep = collect($reponsesData)->firstWhere('question_id', $q->id);

                return [
                    'question_id' => $q->id,
                    'texte' => $q->texte,
                    'type' => $q->type,
                    'points' => $q->points,
                    'est_correct' => $rep['est_correct'] ?? false,
                    'choix_id_donne' => $rep['choix_id'] ?? null,
                    'reponse_texte' => $rep['reponse_texte'] ?? null,
                    'score_ia' => $rep['score_ia'] ?? null,
                    'feedback_ia' => $rep['feedback_ia'] ?? null,
                    'points_forts' => $rep['points_forts'] ?? null,
                    'points_amelioration' => $rep['points_amelioration'] ?? null,
                    'points_obtenus' => $rep['points_obtenus'] ?? 0,
                    'bons_choix' => $q->choix->where('est_correct', true)->pluck('id')->values(),
                    'tous_choix' => $q->choix->map(fn($c) => [
                        'id' => $c->id,
                        'texte' => $c->texte,
                        'est_correct' => $c->est_correct,
                    ]),
                ];
            }),
        ]);
    }

    private function authorize_owner_or_admin($user, Formation $formation): void
    {
        if ($user->role === 'admin') return;
        if ($user->role === 'formateur' && $formation->formateur_id === $user->id) return;
        abort(403, 'Non autorisé.');
    }

    private function formatQuiz(Quiz $quiz, ?string $userRole = null): array
    {
        $isLearner = $userRole === 'apprenant';

        return [
            'id' => (string) $quiz->id,
            'module_id' => (string) $quiz->module_id,
            'titre' => $quiz->titre,
            'description' => $quiz->description,
            'seuil_reussite' => $quiz->seuil_reussite,
            'duree_minutes' => $quiz->duree_minutes,
            'nb_tentatives_max' => $quiz->nb_tentatives_max,
            'statut' => $quiz->statut,
            'questions' => $quiz->questions->map(function ($q) use ($isLearner) {
                return [
                    'id' => (string) $q->id,
                    'texte' => $q->texte,
                    'type' => $q->type,
                    'points' => $q->points,
                    'ordre' => $q->ordre,
                    'correction_attendue' => $isLearner ? null : $q->correction_attendue,
                    'choix' => $q->choix->map(fn($c) => [
                        'id' => (string) $c->id,
                        'texte' => $c->texte,
                        'est_correct' => $isLearner ? null : $c->est_correct,
                        'ordre' => $c->ordre,
                    ]),
                ];
            }),
        ];
    }
}