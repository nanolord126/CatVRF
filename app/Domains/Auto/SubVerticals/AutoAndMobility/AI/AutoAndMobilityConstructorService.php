<?php

declare(strict_types=1);

namespace App\Domains\AutoAndMobility\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;

final readonly class AutoAndMobilityConstructorService
{
    public function __construct(
        private OpenAIService $openAIService,
    ) {}

    /**
     * Generate AI-powered mobility recommendations
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
                    'content' => 'You are a mobility and transportation expert for CatVRF marketplace. Provide helpful recommendations for taxi, car rental, and auto purchase options.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.7);

            return $this->parseRecommendationResponse($response);
        } catch (\Exception $e) {
            Log::error('AutoAndMobility AI recommendation failed', [
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
     * Build prompt for AI recommendations
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

Available Options:
1. Taxi - On-demand rides with professional drivers
2. Car Rental - Self-drive vehicles for short/long term
3. Auto Sales - New and used vehicles for purchase

Please provide:
1. Best mobility option based on the query
2. Specific recommendations within that option
3. Alternative options if applicable
4. Estimated cost range (if applicable)
5. Key considerations for the user

Response format: JSON with keys: recommended_option, recommendations, alternatives, cost_estimate, considerations.
PROMPT;
    }

    /**
     * Parse AI recommendation response
     */
    private function parseRecommendationResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'recommendations' => $data['recommendations'] ?? [],
                'recommended_option' => $data['recommended_option'] ?? null,
                'alternatives' => $data['alternatives'] ?? [],
                'cost_estimate' => $data['cost_estimate'] ?? null,
                'considerations' => $data['considerations'] ?? [],
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse AI recommendation response', [
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
     * Generate route optimization suggestions
     */
    public function optimizeRoute(
        array $waypoints,
        array $constraints = [],
    ): array {
        $prompt = $this->buildRouteOptimizationPrompt($waypoints, $constraints);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a route optimization expert. Provide efficient route suggestions considering time, distance, and traffic.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ], temperature: 0.3);

            return $this->parseRouteResponse($response);
        } catch (\Exception $e) {
            Log::error('Route optimization failed', [
                'error' => $e->getMessage(),
                'waypoints' => $waypoints,
            ]);

            return [
                'optimized_route' => $waypoints,
                'error' => 'Route optimization service temporarily unavailable',
            ];
        }
    }

    /**
     * Build prompt for route optimization
     */
    private function buildRouteOptimizationPrompt(array $waypoints, array $constraints): string
    {
        $waypointsStr = implode(' -> ', $waypoints);
        $constraintsStr = !empty($constraints) ? implode(', ', $constraints) : 'None';

        return <<<PROMPT
Waypoints: {$waypointsStr}
Constraints: {$constraintsStr}

Please provide:
1. Optimized order of waypoints
2. Estimated total distance
3. Estimated total time
4. Traffic considerations
5. Alternative routes if applicable

Response format: JSON with keys: optimized_order, total_distance_km, total_time_minutes, traffic_notes, alternatives.
PROMPT;
    }

    /**
     * Parse route optimization response
     */
    private function parseRouteResponse(string $response): array
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return [
                'optimized_route' => $data['optimized_order'] ?? [],
                'total_distance_km' => $data['total_distance_km'] ?? null,
                'total_time_minutes' => $data['total_time_minutes'] ?? null,
                'traffic_notes' => $data['traffic_notes'] ?? [],
                'alternatives' => $data['alternatives'] ?? [],
            ];
        } catch (\JsonException $e) {
            Log::error('Failed to parse route optimization response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [
                'optimized_route' => $waypoints,
                'error' => 'Failed to process route response',
            ];
        }
    }

    /**
     * Generate dynamic pricing suggestions for taxi/rental
     */
    public function suggestPricing(
        string $subVertical,
        array $factors = [],
    ): array {
        $prompt = $this->buildPricingPrompt($subVertical, $factors);

        try {
            $response = $this->openAIService->chat([
                [
                    'role' => 'system',
                    'content' => 'You are a pricing expert for mobility services. Suggest optimal pricing based on demand, supply, and market conditions.',
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
                'sub_vertical' => $subVertical,
            ]);

            return [
                'suggested_price' => null,
                'confidence' => 0,
                'error' => 'Pricing service temporarily unavailable',
            ];
        }
    }

    /**
     * Build prompt for pricing suggestions
     */
    private function buildPricingPrompt(string $subVertical, array $factors): string
    {
        $factorsStr = !empty($factors) ? json_encode($factors, JSON_PRETTY_PRINT) : '{}';

        return <<<PROMPT
Sub-vertical: {$subVertical}
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
