<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlmCorrectionService
{
    public function corrigerReponseLibre(
        string  $questionTexte,
        string  $reponseApprenant,
        ?string $correctionAttendue = null,
        string  $contexteFormation  = '',
        int     $pointsMax          = 10
    ): array {
        if (!trim($reponseApprenant)) {
            return $this->resultatVide('Aucune réponse fournie.');
        }

        try {
            return $this->corrigerViaGemini(
                $questionTexte,
                $reponseApprenant,
                $correctionAttendue,
                $contexteFormation,
                $pointsMax
            );
        } catch (\Throwable $e) {
            Log::warning('Gemini indisponible, fallback : ' . $e->getMessage());

            return $this->corrigerParMotsCles(
                $reponseApprenant,
                $correctionAttendue ?? '',
                $pointsMax
            );
        }
    }

    // ── Correction via Gemini API ────────────────────────────
   
private function corrigerViaGemini(
    string  $questionTexte,
    string  $reponseApprenant,
    ?string $correctionAttendue,
    string  $contexteFormation,
    int     $pointsMax
): array {
    $baseUrl = rtrim(config('services.gemini.base_url'), '/');
    $apiKey  = config('services.gemini.api_key', '');

    if (empty($apiKey)) {
        throw new \RuntimeException('Clé API Gemini manquante dans .env');
    }

    // ✅ Liste de modèles à essayer dans l'ordre (fallback automatique)
    $models = [
        config('services.gemini.model', 'gemini-2.5-flash'),
        'gemini-1.5-flash',        // fallback si 2.5 surchargé
        'gemini-1.5-flash-8b',     // fallback léger
    ];

    $prompt = $this->construirePrompt(
        $questionTexte,
        $reponseApprenant,
        $correctionAttendue,
        $contexteFormation,
        $pointsMax
    );

    $payload = [
        'contents' => [[
            'role'  => 'user',
            'parts' => [[
                'text' =>
                    "Tu es un correcteur pédagogique strict. " .
                    "Retourne uniquement un JSON valide, sans texte supplémentaire.\n\n" .
                    $prompt,
            ]],
        ]],
        'generationConfig' => [
            'temperature'      => 0.2,
            'responseMimeType' => 'application/json',
        ],
    ];

    $lastException = null;

    foreach ($models as $modelIndex => $model) {
        $endpoint = $baseUrl . '/models/' . $model . ':generateContent?key=' . $apiKey;

        // ✅ Retry 2 fois par modèle avec backoff exponentiel
        $maxRetries = 2;
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {

            if ($attempt > 0) {
                // Attente exponentielle : 1s, 2s
                sleep($attempt);
            }

            try {
                Log::info('GEMINI request', [
                    'model'   => $model,
                    'attempt' => $attempt + 1,
                ]);

                $response = Http::timeout(30)
                    ->acceptJson()
                    ->asJson()
                    ->withoutVerifying()
                    ->post($endpoint, $payload);

                Log::info('GEMINI status', [
                    'status' => $response->status(),
                    'model'  => $model,
                ]);

                // ✅ 503 ou 429 = surchargé, on passe au modèle suivant
                if ($response->status() === 503 || $response->status() === 429) {
                    Log::warning("GEMINI modèle {$model} surchargé (HTTP {$response->status()}), passage au modèle suivant...");
                    $lastException = new \RuntimeException(
                        "Modèle {$model} surchargé : HTTP " . $response->status()
                    );
                    break; // Passe au prochain modèle
                }

                if ($response->failed()) {
                    throw new \RuntimeException(
                        "GEMINI HTTP {$response->status()} avec {$model} : " . $response->body()
                    );
                }

                $data    = $response->json();
                $content = data_get($data, 'candidates.0.content.parts.0.text', '');

                if (empty(trim((string) $content))) {
                    throw new \RuntimeException("Réponse vide du modèle {$model}");
                }

                Log::info("GEMINI succès avec modèle {$model}");
                return $this->parseReponseIA((string) $content, $pointsMax);

            } catch (\RuntimeException $e) {
                $lastException = $e;

                // Si c'est un 503/429, on sort de la boucle de retry
                if (str_contains($e->getMessage(), 'surchargé')) {
                    break;
                }

                // Sinon on retry
                if ($attempt < $maxRetries) {
                    Log::warning("GEMINI tentative {$attempt} échouée pour {$model}, retry...");
                }
            }
        }
    }

    // Tous les modèles ont échoué
    throw new \RuntimeException(
        'Tous les modèles Gemini sont indisponibles. Dernier erreur : ' .
        ($lastException?->getMessage() ?? 'Inconnue')
    );
}

    // ── Fallback par mots-clés si Gemini indisponible ─────────
   
private function corrigerParMotsCles(
    string $reponseApprenant,
    string $correctionAttendue,
    int    $pointsMax
): array {
    if (empty(trim($correctionAttendue))) {
        return [
            'score'               => 50.0,
            'est_correct'         => false,
            'feedback'            => '⚠️ Correction automatique (IA indisponible). Votre réponse a été enregistrée.',
            'points_forts'        => '',
            'points_amelioration' => 'Vérifiez votre réponse avec votre formateur.',
        ];
    }

    $normalize = fn(string $s) => array_values(array_filter(
        explode(' ', strtolower(preg_replace('/[^a-z0-9àâéèêëîïôùûü\s]/i', ' ', $s)))
    ));

    $motsReponse    = $normalize($reponseApprenant);
    $motsAttendus   = $normalize($correctionAttendue);
    $nbAttendus     = count($motsAttendus);
    $score          = 0.0;

    if ($nbAttendus > 0) {
        $communs = count(array_intersect($motsReponse, $motsAttendus));
        // ✅ Score plus généreux : 70% si au moins la moitié des mots sont présents
        $ratio   = $communs / $nbAttendus;
        $score   = min(90.0, round($ratio * 100, 2));
    }

    // ✅ Bonus si la réponse contient exactement le mot attendu (correspondance directe)
    $reponseLower  = strtolower(trim($reponseApprenant));
    $attenduLower  = strtolower(trim($correctionAttendue));
    if (str_contains($attenduLower, $reponseLower) || str_contains($reponseLower, $attenduLower)) {
        $score = max($score, 75.0);
    }

    $estCorrect = $score >= 60;

    return [
        'score'               => $score,
        'est_correct'         => $estCorrect,
        'feedback'            => $estCorrect
            ? '✅ Bonne réponse (correction automatique — IA temporairement indisponible).'
            : '❌ Réponse insuffisante (correction automatique — IA temporairement indisponible).',
        'points_forts'        => $estCorrect ? 'Les éléments clés sont présents.' : '',
        'points_amelioration' => $estCorrect
            ? ''
            : 'Reformulez en incluant les termes clés du cours. La correction exacte sera visible après la reprise de service IA.',
    ];
}

    // ── Parser le JSON retourné par Gemini ────────────────────
    private function parseReponseIA(string $content, int $pointsMax): array
    {
        $content = trim($content);
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        $data = json_decode($content, true);

        if (!is_array($data) || !array_key_exists('score', $data)) {
            Log::warning('GEMINI JSON non parsable', [
                'content' => substr($content, 0, 300),
            ]);

            throw new \RuntimeException('JSON invalide : ' . substr($content, 0, 100));
        }

        $score = max(0.0, min(100.0, (float) ($data['score'] ?? 0)));

        return [
            'score'               => $score,
            'est_correct'         => (bool) ($data['est_correct'] ?? ($score >= 60)),
            'feedback'            => (string) ($data['feedback'] ?? 'Réponse évaluée.'),
            'points_forts'        => (string) ($data['points_forts'] ?? ''),
            'points_amelioration' => (string) ($data['points_amelioration'] ?? ''),
        ];
    }

    // ── Construire le prompt ──────────────────────────────────
    private function construirePrompt(
        string  $question,
        string  $reponse,
        ?string $correctionAttendue,
        string  $contexte,
        int     $pointsMax
    ): string {
        $correction = $correctionAttendue ?: 'Non fournie';

        return <<<PROMPT
Contexte : {$contexte}
Question : {$question}
Correction attendue : {$correction}
Réponse de l'apprenant : {$reponse}

Évalue la réponse de l'apprenant sur 100.

Réponds UNIQUEMENT avec ce JSON valide, sans markdown et sans texte autour :
{"score":75,"est_correct":true,"feedback":"Bonne réponse.","points_forts":"Bonne compréhension.","points_amelioration":""}

Règles :
- score = nombre entre 0 et 100
- est_correct = true si score >= 60, sinon false
- feedback = 1 phrase en français
- points_forts = texte court en français
- points_amelioration = texte court en français
PROMPT;
    }

    // ── Résultat vide ─────────────────────────────────────────
    private function resultatVide(string $feedback): array
    {
        return [
            'score'               => 0.0,
            'est_correct'         => false,
            'feedback'            => $feedback,
            'points_forts'        => '',
            'points_amelioration' => '',
        ];
    }
}