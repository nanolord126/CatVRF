<?php

declare(strict_types=1);

namespace App\Jobs\Supermarket;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supermarket\Application\Services\SellerAnalyticsService;
use Modules\Supermarket\Infrastructure\Models\SellerInsight;

final class GenerateAIInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(
        public readonly int $tenantId,
    ) {}

    public function handle(): void
    {
        try {
            $seller = Tenant::find($this->tenantId);
            if (!$seller) {
                Log::warning('Seller not found for AI insights generation', [
                    'tenant_id' => $this->tenantId,
                ]);
                return;
            }

            $analyticsService = app(SellerAnalyticsService::class);
            $analytics = $analyticsService->getDashboard($seller, '30d');
            $lastMonth = $analyticsService->getDashboard($seller, '30d_ago');

            $prompt = $this->buildPrompt($seller, $analytics, $lastMonth);

            try {
                $response = $this->callAI($prompt);
                $insights = $this->parseAIResponse($response);

                foreach ($insights as $insight) {
                    SellerInsight::create([
                        'tenant_id' => $seller->id,
                        ...$insight,
                        'generated_at' => now(),
                        'expires_at' => now()->addHours(36),
                    ]);
                }

                Log::info('AI insights generated successfully via queue', [
                    'tenant_id' => $this->tenantId,
                    'insights_count' => count($insights),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to call AI API in job', [
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate AI insights via queue', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->attempts() >= $this->tries) {
                Log::critical('AI insights generation permanently failed', [
                    'tenant_id' => $this->tenantId,
                ]);
            }

            throw $e;
        }
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
}
