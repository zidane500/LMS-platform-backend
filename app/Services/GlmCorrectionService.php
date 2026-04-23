<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GlmCorrectionService
{
    public function corrigerReponseLibre(
        string $questionTexte,
        string $reponseApprenant,
        ?string $correctionAttendue = null,
        string $contexteFormation = '',
        int $pointsMax = 10
    ): array {
        if (!trim($reponseApprenant)) {
            return [
                'score' => 0,
                'est_correct' => false,
                'feedback' => 'Aucune réponse fournie.',
                'points_forts' => '',
                'points_amelioration' => '',
            ];
        }

        $prompt = $this->construirePrompt(
            $questionTexte,
            $reponseApprenant,
            $correctionAttendue,
            $contexteFormation,
            $pointsMax
        );

        try {
            $response = Http::baseUrl(config('services.ollama.base_url'))
            ->withToken(config('services.ollama.api_key'))
            ->acceptJson()
            ->asJson()
            ->withoutVerifying()
            ->timeout(120)
            ->post('/chat', [
        'model' => config('services.ollama.model'),
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Tu es un correcteur pédagogique strict. Retourne uniquement un JSON valide, sans texte supplémentaire.'
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'stream' => false,
        'format' => 'json',
    ]);
                    
            Log::info('OLLAMA status', ['status' => $response->status()]);
            Log::info('OLLAMA raw body', ['body' => $response->body()]);

            if ($response->failed()) {
                return [
                    'score' => 0,
                    'est_correct' => false,
                    'feedback' => 'Erreur Ollama',
                    'points_forts' => '',
                    'points_amelioration' => '',
                    'debug_error' => $response->body(),
                ];
            }

            $payload = $response->json();
            $content = data_get($payload, 'message.content', '');

            return $this->parseReponseIA((string) $content, $reponseApprenant);

        } catch (\Throwable $e) {
            Log::error('OLLAMA correction error: ' . $e->getMessage());

            return [
                'score' => 0,
                'est_correct' => false,
                'feedback' => 'Erreur Ollama',
                'points_forts' => '',
                'points_amelioration' => '',
                'debug_error' => $e->getMessage(),
            ];
        }
    }

    private function construirePrompt(
        string $question,
        string $reponse,
        ?string $correctionAttendue,
        string $contexte,
        int $pointsMax
    ): string {
        $correctionAttendue = $correctionAttendue ?: 'Non fournie';

        return <<<PROMPT
Contexte de formation :
{$contexte}

Question :
{$question}

Correction attendue :
{$correctionAttendue}

Réponse de l'apprenant :
{$reponse}

Évalue la réponse sur 100.

Réponds UNIQUEMENT en JSON valide avec ce format exact :
{
  "score": 0,
  "est_correct": false,
  "feedback": "",
  "points_forts": "",
  "points_amelioration": ""
}

Règles :
- score = nombre entre 0 et 100 (décimales autorisées)
- est_correct = true si score >= 60, sinon false
- feedback = 1 à 2 phrases en français
- points_forts = texte court
- points_amelioration = texte court
PROMPT;
    }

    private function parseReponseIA(string $content, string $reponseOriginale): array
    {
        $content = trim($content);
        $content = preg_replace('/```json|```/i', '', $content);
        $content = trim($content);

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['score'])) {
            Log::warning('OLLAMA parse failed', ['content' => $content]);

            return [
                'score' => 0,
                'est_correct' => false,
                'feedback' => 'Réponse Ollama non parsable',
                'points_forts' => '',
                'points_amelioration' => '',
                'debug_raw_content' => $content,
            ];
        }

        $score = max(0, min(100, (float) $data['score']));

        return [
            'score' => $score,
            'est_correct' => (bool) ($data['est_correct'] ?? $score >= 60),
            'feedback' => $data['feedback'] ?? 'Réponse évaluée.',
            'points_forts' => $data['points_forts'] ?? '',
            'points_amelioration' => $data['points_amelioration'] ?? '',
        ];
    }
}