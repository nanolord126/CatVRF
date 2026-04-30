<?php

declare(strict_types=1);

namespace App\Domains\LeisureAndEntertainment\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

final readonly class LeisureAndEntertainmentConstructorService
{
    public function __construct(
        private OpenAIService $openAIService,
    ) {}

    public function generateRecommendations(
        string $userQuery,
        array $context = [],
    ): array {
        $prompt = $this->buildRecommendationPrompt($userQuery, $context);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are an entertainment and leisure expert for CatVRF marketplace. Provide helpful recommendations for concerts, cinema, exhibitions, parties, and leisure activities.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.7);

            return $this->parseRecommendationResponse($response);
        } catch (\Exception $e) {
            Log::error('Leisure AI recommendation failed', [
                'error' => $e->getMessage(),
                'query' => $userQuery,
            ]);

            return [
                'recommendations' => [],
                'error' => 'AI recommendation service temporarily unavailable',
            ];
        }
    }

    private function buildRecommendationPrompt(string $userQuery, array $context): string
    {
        $contextStr = '';

        if (!empty($context['location'])) {
            $contextStr .= "Location: {$context['location']}\n";
        }

        if (!empty($context['budget'])) {
            $contextStr .= "Budget: {$context['budget']}\n";
        }

        if (!empty($context['preferences'])) {
            $contextStr .= "Preferences: " . implode(', ', $context['preferences']) . "\n";
        }

        return <<<PROMPT
User Query: {$userQuery}

Context:
{$contextStr}

Available Options:
- Tickets (билетная касса)
- Concerts (концерты)
- Cinema (кино)
- Exhibitions (выставки)
- Parties & Тусовки
- Leisure Activities (активности для досуга)

Please provide:
1. Best entertainment option based on the query
2. Specific recommendations within that option
3. Alternative options if applicable
4. Estimated price range
5. Key considerations for the user

Response format: JSON with keys: recommended_option, recommendations, alternatives, price_estimate, considerations.
PROMPT;
    }

    private function parseRecommendationResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'recommendations' => $data['recommendations'] ?? [],
                'recommended_option' => $data['recommended_option'] ?? null,
                'alternatives' => $data['alternatives'] ?? [],
                'price_estimate' => $data['price_estimate'] ?? null,
                'considerations' => $data['considerations'] ?? [],
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse recommendation response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'recommendations' => [],
                'error' => 'Failed to process AI response',
            ];
        }
    }
}
