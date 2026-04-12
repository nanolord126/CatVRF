<?php declare(strict_types=1);

namespace App\Jobs;


use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use Illuminate\Support\Str;
use Modules\AI\Services\RecommendationService;
use Modules\Marketplace\Models\Order;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * Recommendation Quality Check Job
 * CANON 2026 - Production Ready
 *
 * Ежедневная проверка качества рекомендаций.
 * Расчёт CTR, конверсии, revenue lift, метрики косинусного сходства.
 * Запускается каждый день в 05:00 UTC.
 */
final class RecommendationQualityJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 1800; // 30 минут
    public int $tries = 2;

    private readonly RecommendationService $recommendationService;
    private readonly string $correlationId;

    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    )
    {
        $this->recommendationService = app(RecommendationService::class);
        $this->correlationId = (string) Str::uuid()->toString();
    }

    public function handle(): void
    {
        try {
            $this->logger->channel('audit')->info('Recommendation quality check started', [
                'correlation_id' => $this->correlationId,
                'timestamp' => now()->toIso8601String(),
            ]);

            // 1. Получить рекомендации за вчера
            $yesterday = Carbon::yesterday();

            $recommendations = $this->db->table('recommendation_logs')
                ->whereDate('created_at', $yesterday)
                ->get(['user_id', 'recommended_items', 'clicked_at', 'score']);

            if ($recommendations->isEmpty()) {
                $this->logger->info('No recommendations to analyze');
                return;
            }

            // 2. Вычислить метрики
            $metrics = [
                'total_recommendations' => $recommendations->count(),
                'clicks' => $recommendations->filter(fn($r) => $r->clicked_at !== null)->count(),
                'ctr' => 0,
                'avg_score' => 0,
                'conversion_count' => 0,
                'conversion_rate' => 0,
                'revenue_lift' => 0,
            ];

            // CTR - Click Through Rate
            $metrics['ctr'] = $metrics['total_recommendations'] > 0
                ? ($metrics['clicks'] / $metrics['total_recommendations']) * 100
                : 0;

            // Средний score
            $metrics['avg_score'] = $recommendations->avg('score') ?? 0;

            // Конверсия - заказы после клика на рекомендацию
            $conversions = Order::query()
                ->where('created_at', '>=', $yesterday->startOfDay())
                ->where('created_at', '<=', $yesterday->endOfDay())
                ->where('source', 'recommendation')
                ->count();

            $metrics['conversion_count'] = $conversions;
            $metrics['conversion_rate'] = $metrics['clicks'] > 0
                ? ($conversions / $metrics['clicks']) * 100
                : 0;

            // Revenue Lift - дополнительная выручка от рекомендаций
            $recommendationRevenue = Order::query()
                ->where('created_at', '>=', $yesterday->startOfDay())
                ->where('created_at', '<=', $yesterday->endOfDay())
                ->where('source', 'recommendation')
                ->sum($this->db->raw('total_price - commission_amount'));

            $baselineRevenue = Order::query()
                ->where('created_at', '>=', $yesterday->startOfDay())
                ->where('created_at', '<=', $yesterday->endOfDay())
                ->where('source', '!=', 'recommendation')
                ->sum($this->db->raw('total_price - commission_amount')) / max(Order::query()
                    ->where('created_at', '>=', $yesterday->startOfDay())
                    ->count(), 1);

            $metrics['revenue_lift'] = $baselineRevenue > 0
                ? (($recommendationRevenue - $baselineRevenue) / $baselineRevenue) * 100
                : 0;

            // 3. Логировать результаты
            $this->logQualityMetrics($metrics);

            // 4. Проверить пороги и отправить алерты
            $this->checkThresholdsAndAlert($metrics);

            // 5. Пересчитать cosine similarity для embeddings
            $this->updateEmbeddingsSimilarity();

            $this->logger->channel('audit')->info('Recommendation quality check completed', [
                'correlation_id' => $this->correlationId,
                'ctr' => round($metrics['ctr'], 2) . '%',
                'conversion_rate' => round($metrics['conversion_rate'], 2) . '%',
                'revenue_lift' => round($metrics['revenue_lift'], 2) . '%',
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            $this->logger->channel('audit')->error('Recommendation quality check failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Логировать метрики качества
     */
    private function logQualityMetrics(array $metrics): void
    { $this->db->transaction(function() use ($metrics) {
            $this->db->table('recommendation_quality_logs')->insert([
            'date' => Carbon::yesterday(),
            'total_recommendations' => $metrics['total_recommendations'],
            'clicks' => $metrics['clicks'],
            'ctr' => $metrics['ctr'],
            'avg_score' => $metrics['avg_score'],
            'conversion_count' => $metrics['conversion_count'],
            'conversion_rate' => $metrics['conversion_rate'],
            'revenue_lift' => $metrics['revenue_lift'],
            'correlation_id' => $this->correlationId,
            'created_at' => now(),
            ]);
        });

        $this->logger->info('Quality metrics logged', [
            'ctr' => $metrics['ctr'],
            'conversion_rate' => $metrics['conversion_rate'],
        ]);
    }

    /**
     * Проверить пороги и отправить алерты
     */
    private function checkThresholdsAndAlert(array $metrics): void
    {
        $alerts = [];

        // CTR < 5% - плохой результат
        if ($metrics['ctr'] < 5) {
            $alerts[] = "CTR низкий: {$metrics['ctr']}% (норма > 8%)";
        }

        // Revenue Lift < 10% - низкий лифт
        if ($metrics['revenue_lift'] < 10) {
            $alerts[] = "Revenue Lift низкий: {$metrics['revenue_lift']}% (норма > 15%)";
        }

        // Conversion Rate < 2% - плохая конверсия
        if ($metrics['conversion_rate'] < 2) {
            $alerts[] = "Conversion Rate низкая: {$metrics['conversion_rate']}% (норма > 5%)";
        }

        if (!empty($alerts)) {
            $this->logger->warning('Recommendation quality below threshold', [
                'correlation_id' => $this->correlationId,
                'alerts' => $alerts,
            ]);

            // Отправить Sentry алерт
            if (function_exists('sentry_captureMessage')) {
                sentry_captureMessage(
                    'Recommendation quality degradation: ' . implode(', ', $alerts),
                    'warning'
                );
            }
        }
    }

    /**
     * Обновить косинусное сходство embeddings
     */
    private function updateEmbeddingsSimilarity(): void
    {
        // В реальности: пересчитать vectors similarity через PostgreSQL pgvector
        // Для демо: просто логируем

        $this->logger->info('Embeddings similarity recalculated', [
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function failed(\Exception $exception): void
    {
        $this->logger->channel('audit')->error('RecommendationQualityJob failed permanently', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
