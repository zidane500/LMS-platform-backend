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
        $model   = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($apiKey)) {
            throw new \RuntimeException('Clé API Gemini manquante dans .env');
        }

        $prompt = $this->construirePrompt(
            $questionTexte,
            $reponseApprenant,
            $correctionAttendue,
            $contexteFormation,
            $pointsMax
        );

        $endpoint = $baseUrl . '/models/' . $model . ':generateContent?key=' . $apiKey;

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' =>
                                "Tu es un correcteur pédagogique strict. " .
                                "Retourne uniquement un JSON valide, sans texte supplémentaire.\n\n" .
                                $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        Log::info('GEMINI request', [
            'endpoint' => $baseUrl . '/models/' . $model . ':generateContent',
            'model' => $model,
        ]);

        $response = Http::timeout(120)
            ->acceptJson()
            ->asJson()
            ->withoutVerifying() // temporaire pour éviter cURL error 60 en local
            ->post($endpoint, $payload);

        Log::info('GEMINI status', ['status' => $response->status()]);
        Log::info('GEMINI body', ['body' => substr($response->body(), 0, 800)]);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Gemini API erreur HTTP ' . $response->status() . ' : ' . $response->body()
            );
        }

        $data = $response->json();

        // Gemini retourne généralement le texte ici :
        $content = data_get($data, 'candidates.0.content.parts.0.text', '');

        if (empty(trim((string) $content))) {
            throw new \RuntimeException('Réponse vide du modèle Gemini');
        }

        return $this->parseReponseIA((string) $content, $pointsMax);
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
                'feedback'            => 'Correction automatique (IA indisponible). Réponse enregistrée.',
                'points_forts'        => '',
                'points_amelioration' => 'Vérifiez votre réponse avec votre formateur.',
            ];
        }

        $normalize = fn(string $s) => array_values(array_filter(
            explode(' ', strtolower(preg_replace('/[^a-z0-9àâéèêëîïôùûü\s]/i', ' ', $s)))
        ));

        $motsReponse  = $normalize($reponseApprenant);
        $motsAttendus = $normalize($correctionAttendue);

        $nbAttendus = count($motsAttendus);
        $score = 0.0;

        if ($nbAttendus > 0) {
            $communs = count(array_intersect($motsReponse, $motsAttendus));
            $score   = min(85.0, round(($communs / $nbAttendus) * 100, 2));
        }

        $estCorrect = $score >= 60;

        return [
            'score'               => $score,
            'est_correct'         => $estCorrect,
            'feedback'            => $estCorrect
                ? 'Bonne réponse (correction automatique — IA temporairement indisponible).'
                : 'Réponse insuffisante (correction automatique — IA temporairement indisponible).',
            'points_forts'        => $estCorrect ? 'Les mots-clés principaux sont présents.' : '',
            'points_amelioration' => $estCorrect ? '' : 'Reformulez votre réponse en incluant les termes clés du cours.',
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