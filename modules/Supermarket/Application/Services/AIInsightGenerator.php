<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use App\Jobs\Supermarket\GenerateSellerInsightsJob;
use Modules\Supermarket\Infrastructure\Models\SellerInsight;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Collection;

final class AIInsightGenerator
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }
    public function generateForSeller(Tenant $seller): Collection
    {
        return $this->withSpan(
            'ai_insight.generate',
            function () use ($seller) {
                // Fraud check before AI insight generation
                $this->fraudControl->check([
                    'operation_type' => 'ai_insight_generate',
                    'vertical' => 'supermarket',
                    'user_id' => $seller->owner_id ?? null,
                    'tenant_id' => $seller->id,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                // Dispatch job for async AI processing
                dispatch(new \App\Jobs\Supermarket\GenerateAIInsightsJob($seller->id))
                    ->onQueue('supermarket-high')
                    ->delay(now()->addSeconds(5));

                Log::info('AI insights generation dispatched to queue', [
                    'tenant_id' => $seller->id,
                ]);

                // Return cached insights if available
                return $this->getCachedInsights($seller);
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'ai_insight_generate',
                tenantId: (string) $seller->id,
            ),
        );
    }

    private function buildPrompt(Tenant $seller, array $current, array $previous): string
    {
        $revenueGrowth = $previous['total_revenue'] > 0 
            ? round((($current['total_revenue'] - $previous['total_revenue']) / $previous['total_revenue']) * 100, 2)
            : 0;

        return "Ты — старший аналитик маркетплейса продуктов питания с 12-летним опытом.

Продавец: {$seller->shop_name}
Период: последние 30 дней

Ключевые метрики:
- Выручка: {$current['total_revenue']} ₽ ({$revenueGrowth}% к прошлому периоду)
- Количество заказов: {$current['orders_count']}
- Средний чек: {$current['avg_order_value']} ₽
- Возвраты: {$current['returns_rate']}%
- Топ-5 категорий: " . json_encode(array_slice($current['sub_vertical_stats'], 0, 5)) . "
- Топ-10 товаров: " . json_encode(array_slice($current['top_products'], 0, 10)) . "

Сгенерируй от 4 до 6 коротких инсайтов (каждый — максимум 2 предложения).

Для каждого инсайта обязательно укажи:
- title: короткий заголовок
- description: описание
- impact: high / medium / low
- actionable: true / false

Стиль: прямой, конкретный, без воды. Используй цифры. 
Фокус на том, что продавец может сделать прямо сейчас для роста выручки и снижения возвратов.

Ответ в формате JSON массива объектов.";
    }

    private function callAI(string $prompt): string
    {
        return $this->withSpan(
            'ai_insight.call_api',
            function () use ($prompt) {
                try {
            $client = new \GuzzleHttp\Client();
            
            $response = $client->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты — жесткий, но полезный бизнес-аналитик продуктового маркетплейса. Говоришь коротко, по делу, с цифрами. Избегаешь общих фраз. Всегда даёшь конкретные действия.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ],
                'timeout' => 30,
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            $this->recordSpanException($e);
            Log::error('AI API call failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'ai_insight_call_api',
            ),
        );
    }

    private function parseAIResponse(string $response): array
    {
        try {
            $data = json_decode($response, true);
            $content = $data['choices'][0]['message']['content'] ?? '[]';
            $insights = json_decode($content, true);

            if (!is_array($insights)) {
                return [];
            }

            return array_map(function ($insight) {
                return [
                    'type' => $this->determineInsightType($insight),
                    'title' => $insight['title'] ?? 'Инсайт',
                    'description' => $insight['description'] ?? '',
                    'value' => $insight['value'] ?? null,
                    'impact' => $insight['impact'] ?? 'medium',
                    'actionable' => $insight['actionable'] ?? false,
                ];
            }, $insights);
        } catch (\Exception $e) {
            Log::error('Failed to parse AI response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            return [];
        }
    }

    private function determineInsightType(array $insight): string
    {
        $description = strtolower($insight['description'] ?? '');
        $title = strtolower($insight['title'] ?? '');

        if (str_contains($description, 'рост') || str_contains($title, 'рост')) {
            return 'revenue_growth';
        }

        if (str_contains($description, 'товар') || str_contains($title, 'товар')) {
            return 'top_product';
        }

        if (str_contains($description, 'цен') || str_contains($title, 'цен')) {
            return 'pricing_recommendation';
        }

        if (str_contains($description, 'возврат') || str_contains($title, 'возврат')) {
            return 'return_problem';
        }

        if (str_contains($description, 'категори') || str_contains($title, 'категори')) {
            return 'category_performance';
        }

        return 'general';
    }

    public function getCachedInsights(Tenant $seller): Collection
    {
        return SellerInsight::where('tenant_id', $seller->id)
            ->where('expires_at', '>', now())
            ->orderBy('impact', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function refreshExpiredInsights(): int
    {
        return $this->withSpan(
            'ai_insight.refresh_expired',
            function () {
                $expiredInsights = SellerInsight::where('expires_at', '<=', now())
            ->get()
            ->groupBy('tenant_id');

        $refreshedCount = 0;

        foreach ($expiredInsights as $tenantId => $insights) {
            try {
                $seller = Tenant::find($tenantId);
                if ($seller) {
                    $this->generateForSeller($seller);
                    $refreshedCount++;
                }
            } catch (\Exception $e) {
                $this->recordSpanException($e);
                Log::error('Failed to refresh insights for tenant', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $refreshedCount;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'ai_insight_refresh_expired',
            ),
        );
    }
}
