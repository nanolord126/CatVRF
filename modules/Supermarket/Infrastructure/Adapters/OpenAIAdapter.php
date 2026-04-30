<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Adapters;

use App\Traits\WithTelemetry;
use App\Jobs\Supermarket\GenerateSellerInsightsJob;
use App\Jobs\Supermarket\GenerateProductRecommendationsJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Adapter for Supermarket vertical.
 * 
 * Provides integration with OpenAI API for AI-powered features like:
 * - Product recommendations
 * - Demand forecasting
 * - Inventory optimization suggestions
 * - Customer sentiment analysis
 */
final class OpenAIAdapter
{
    use WithTelemetry;

    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
        $this->endpoint = config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate AI insights for seller analytics.
     */
    public function generateSellerInsights(array $analyticsData): array
    {
        return $this->withSpan(
            'openai.generate_seller_insights',
            function () use ($analyticsData) {
                $prompt = $this->buildInsightsPrompt($analyticsData);

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->endpoint, [
                            'model' => $this->model,
                            'temperature' => 0.7,
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => 'You are a senior business analyst for a food marketplace. Provide concise, data-driven insights with specific actionable recommendations.',
                                ],
                                [
                                    'role' => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                            'response_format' => ['type' => 'json_object'],
                        ]);

                    if (!$response->successful()) {
                        Log::error('OpenAI API error', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                        throw new \RuntimeException('OpenAI API request failed');
                    }

                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? '[]';
                    $insights = json_decode($content, true);

                    return is_array($insights) ? $insights : [];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Failed to generate seller insights', [
                        'error' => $e->getMessage(),
                    ]);
                    return [];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'openai_generate_insights',
            ),
        );
    }

    /**
     * Generate product recommendations based on order history.
     */
    public function generateProductRecommendations(array $orderHistory, int $limit = 10): array
    {
        return $this->withSpan(
            'openai.generate_recommendations',
            function () use ($orderHistory, $limit) {
                $prompt = $this->buildRecommendationsPrompt($orderHistory, $limit);

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->endpoint, [
                            'model' => $this->model,
                            'temperature' => 0.5,
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => 'You are a recommendation engine for a food marketplace. Suggest products based on purchase history and seasonal trends.',
                                ],
                                [
                                    'role' => 'user',
                                    'content' => $prompt,
                                ],
                            ],
                            'response_format' => ['type' => 'json_object'],
                        ]);

                    if (!$response->successful()) {
                        Log::error('OpenAI API error for recommendations', [
                            'status' => $response->status(),
                        ]);
                        return [];
                    }

                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? '{"recommendations":[]}';
                    $result = json_decode($content, true);

                    return $result['recommendations'] ?? [];
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Failed to generate recommendations', [
                        'error' => $e->getMessage(),
                    ]);
                    return [];
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'openai_recommendations',
            ),
        );
    }

    /**
     * Build prompt for seller insights.
     */
    private function buildInsightsPrompt(array $analytics): string
    {
        return "Analyze the following supermarket seller metrics and provide 4-6 actionable insights:

Revenue: {$analytics['total_revenue']} ₽
Orders: {$analytics['orders_count']}
AOV: {$analytics['avg_order_value']} ₽
Returns: {$analytics['returns_rate']}%
Top Categories: " . json_encode($analytics['sub_vertical_stats']) . "

For each insight, provide:
- title: short heading
- description: detailed explanation with numbers
- impact: high/medium/low
- actionable: true/false

Response as JSON array.";
    }

    /**
     * Build prompt for product recommendations.
     */
    private function buildRecommendationsPrompt(array $history, int $limit): string
    {
        return "Based on the following purchase history, suggest {$limit} products:

" . json_encode($history, JSON_PRETTY_PRINT) . "

Consider:
- Seasonal trends
- Complementary products
- Frequently bought together items

Response as JSON with 'recommendations' array containing product_ids and reason.";
    }
}
