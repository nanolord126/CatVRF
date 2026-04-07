<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services\AI;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\PaymentRecord;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * AI-конструктор для модуля Payment.
 *
 * Анализирует историю платежей пользователя/tenant:
 * - Рекомендует оптимального провайдера
 * - Выявляет паттерны отказов и ошибок
 * - Прогнозирует конверсию по провайдерам
 */
final readonly class PaymentConstructorService
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private FraudControlService $fraud,
        private AuditService $audit,
        private CacheRepository $cache,
    ) {}

    /**
     * Проанализировать платёжную историю и дать рекомендации.
     *
     * @return array{analysis: array<string, mixed>, recommendations: array<int, array<string, mixed>>}
     */
    public function analyzeAndRecommend(int $tenantId, string $correlationId): array
    {
        $cacheKey = "payment_ai_analysis:{$tenantId}";
        $cached = $this->cache->get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $analysis = $this->buildAnalysis($tenantId);
        $recommendations = $this->generateRecommendations($analysis);

        $result = [
            'analysis' => $analysis,
            'recommendations' => $recommendations,
        ];

        $this->cache->put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        $this->logger->info('Payment AI analysis completed', [
            'tenant_id' => $tenantId,
            'total_transactions' => $analysis['total_count'],
            'recommendations_count' => count($recommendations),
            'correlation_id' => $correlationId,
        ]);

        $this->audit->record(
            action: 'payment_ai_analysis',
            subjectType: 'tenant',
            subjectId: $tenantId,
            newValues: ['recommendations_count' => count($recommendations)],
            correlationId: $correlationId,
        );

        return $result;
    }

    /**
     * Собрать аналитику по платежам tenant.
     *
     * @return array<string, mixed>
     */
    private function buildAnalysis(int $tenantId): array
    {
        $records = PaymentRecord::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get();

        $totalCount = $records->count();
        $capturedCount = $records->where('status', PaymentStatus::CAPTURED)->count();
        $failedCount = $records->where('status', PaymentStatus::FAILED)->count();
        $refundedCount = $records->where('status', PaymentStatus::REFUNDED)->count();
        $totalAmount = $records->sum('amount_kopecks');

        $providerStats = [];
        foreach (PaymentProvider::cases() as $provider) {
            $providerRecords = $records->where('provider_code', $provider);
            $providerTotal = $providerRecords->count();

            if ($providerTotal > 0) {
                $providerCaptured = $providerRecords->where('status', PaymentStatus::CAPTURED)->count();
                $providerStats[$provider->value] = [
                    'total' => $providerTotal,
                    'captured' => $providerCaptured,
                    'success_rate' => round($providerCaptured / $providerTotal * 100, 2),
                    'total_amount' => $providerRecords->sum('amount_kopecks'),
                ];
            }
        }

        return [
            'total_count' => $totalCount,
            'captured_count' => $capturedCount,
            'failed_count' => $failedCount,
            'refunded_count' => $refundedCount,
            'total_amount_kopecks' => $totalAmount,
            'success_rate' => $totalCount > 0 ? round($capturedCount / $totalCount * 100, 2) : 0.0,
            'provider_stats' => $providerStats,
        ];
    }

    /**
     * Сгенерировать рекомендации на основе анализа.
     *
     * @param array<string, mixed> $analysis
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        // 1. Общий success_rate ниже 80% → тревога
        if ($analysis['success_rate'] < 80.0 && $analysis['total_count'] > 10) {
            $recommendations[] = [
                'type' => 'low_success_rate',
                'priority' => 'high',
                'message' => 'Конверсия платежей ниже 80%. Рекомендуем проверить настройки провайдеров.',
                'current_rate' => $analysis['success_rate'],
            ];
        }

        // 2. Много возвратов (>10%) → подозрительно
        $refundRate = $analysis['total_count'] > 0
            ? ($analysis['refunded_count'] / $analysis['total_count']) * 100
            : 0.0;

        if ($refundRate > 10.0) {
            $recommendations[] = [
                'type' => 'high_refund_rate',
                'priority' => 'high',
                'message' => 'Доля возвратов превышает 10%. Возможна фрод-активность.',
                'refund_rate' => round($refundRate, 2),
            ];
        }

        // 3. Рекомендация лучшего провайдера
        $bestProvider = $this->findBestProvider($analysis['provider_stats']);

        if ($bestProvider !== null) {
            $recommendations[] = [
                'type' => 'optimal_provider',
                'priority' => 'medium',
                'message' => "Оптимальный провайдер по конверсии: {$bestProvider}",
                'provider' => $bestProvider,
            ];
        }

        // 4. Если всё хорошо
        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'healthy',
                'priority' => 'low',
                'message' => 'Платёжная система работает стабильно.',
            ];
        }

        return $recommendations;
    }

    /**
     * Определить провайдера с лучшей конверсией.
     *
     * @param array<string, array<string, mixed>> $providerStats
     */
    private function findBestProvider(array $providerStats): ?string
    {
        $best = null;
        $bestRate = 0.0;

        foreach ($providerStats as $provider => $stats) {
            if ($stats['total'] >= 5 && $stats['success_rate'] > $bestRate) {
                $bestRate = $stats['success_rate'];
                $best = $provider;
            }
        }

        return $best;
    }
}
