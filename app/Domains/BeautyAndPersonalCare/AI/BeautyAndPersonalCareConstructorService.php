<?php

declare(strict_types=1);

namespace App\Domains\BeautyAndPersonalCare\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

final readonly class BeautyAndPersonalCareConstructorService
{
    public function __construct(
        private OpenAIService $openAIService,
    ) {}

    /**
     * Generate AI-powered beauty service recommendations
     */
    public function generateRecommendations(
        string $userQuery,
        array $context = [],
    ): array {
        $prompt = $this->buildRecommendationPrompt($userQuery, $context);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a beauty and personal care expert for CatVRF marketplace. Provide helpful recommendations for beauty services, treatments, and professionals.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.7);

            return $this->parseRecommendationResponse($response);
        } catch (\Exception $e) {
            Log::error('Beauty AI recommendation failed', [
                'error' => $e->getMessage(),
                'query' => $userQuery,
            ]);

            return [
                'recommendations' => [],
                'error' => 'AI recommendation service temporarily unavailable',
            ];
        }
    }

    /**
     * Suggest beauty treatments based on user profile
     */
    public function suggestTreatments(
        array $userProfile,
        array $preferences = [],
    ): array {
        $prompt = $this->buildTreatmentPrompt($userProfile, $preferences);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a beauty treatment expert. Suggest appropriate treatments based on user profile and preferences.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.6);

            return $this->parseTreatmentResponse($response);
        } catch (\Exception $e) {
            Log::error('Treatment suggestion failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'treatments' => [],
                'error' => 'AI suggestion service temporarily unavailable',
            ];
        }
    }

    /**
     * Generate pricing suggestions for beauty services
     */
    public function suggestPricing(
        string $serviceType,
        array $factors = [],
    ): array {
        $prompt = $this->buildPricingPrompt($serviceType, $factors);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a beauty service pricing expert. Suggest optimal pricing based on market conditions, location, and service quality.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.4);

            return $this->parsePricingResponse($response);
        } catch (\Exception $e) {
            Log::error('Pricing suggestion failed', [
                'error' => $e->getMessage(),
                'service_type' => $serviceType,
            ]);

            return [
                'suggested_price' => null,
                'confidence' => 0,
                'error' => 'Pricing service temporarily unavailable',
            ];
        }
    }

    /**
     * Build prompt for recommendations
     */
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

Available Services:
- Hair styling and coloring
- Makeup and cosmetics
- Nail care
- Skincare treatments
- Massage and spa
- Beauty consultations

Please provide:
1. Best beauty service based on the query
2. Specific recommendations within that service
3. Alternative services if applicable
4. Estimated price range
5. Key considerations for the user

Response format: JSON with keys: recommended_service, recommendations, alternatives, price_estimate, considerations.
PROMPT;
    }

    /**
     * Build prompt for treatment suggestions
     */
    private function buildTreatmentPrompt(array $userProfile, array $preferences): string
    {
        $profileStr = json_encode($userProfile, JSON_PRETTY_PRINT);
        $preferencesStr = !empty($preferences) ? json_encode($preferences, JSON_PRETTY_PRINT) : '{}';

        return <<<PROMPT
User Profile:
{$profileStr}

Preferences:
{$preferencesStr}

Please suggest:
1. Appropriate beauty treatments
2. Treatment frequency recommendations
3. Contraindications to consider
4. Expected results
5. Price range estimates

Response format: JSON with keys: treatments (array with name, description, frequency, contraindications, results, price_range), general_advice.
PROMPT;
    }

    /**
     * Build prompt for pricing suggestions
     */
    private function buildPricingPrompt(string $serviceType, array $factors): string
    {
        $factorsStr = !empty($factors) ? json_encode($factors, JSON_PRETTY_PRINT) : '{}';

        return <<<PROMPT
Service Type: {$serviceType}
Factors: {$factorsStr}

Please suggest:
1. Optimal price point
2. Price range (min/max)
3. Confidence level (0-1)
4. Key factors influencing the price
5. Market trends

Response format: JSON with keys: suggested_price, price_range_min, price_range_max, confidence, key_factors, market_trends.
PROMPT;
    }

    /**
     * Parse recommendation response
     */
    private function parseRecommendationResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'recommendations' => $data['recommendations'] ?? [],
                'recommended_service' => $data['recommended_service'] ?? null,
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

    /**
     * Parse treatment response
     */
    private function parseTreatmentResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'treatments' => $data['treatments'] ?? [],
                'general_advice' => $data['general_advice'] ?? [],
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse treatment response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'treatments' => [],
                'error' => 'Failed to process treatment response',
            ];
        }
    }

    /**
     * Parse pricing response
     */
    private function parsePricingResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'suggested_price' => $data['suggested_price'] ?? null,
                'price_range' => [
                    'min' => $data['price_range_min'] ?? null,
                    'max' => $data['price_range_max'] ?? null,
                ],
                'confidence' => $data['confidence'] ?? 0,
                'key_factors' => $data['key_factors'] ?? [],
                'market_trends' => $data['market_trends'] ?? [],
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse pricing response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'suggested_price' => null,
                'confidence' => 0,
                'error' => 'Failed to process pricing response',
            ];
        }
    }
}
