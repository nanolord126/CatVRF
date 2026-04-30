<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

/**
 * RevenueTrackingService — Сервис отслеживания выручки
 * 
 * Отслеживает выручку по каналам, категориям, периодам, с разделением на чистую/брутто
 */
final class RevenueTrackingService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Записать выручку от заказа
     */
    public function recordOrderRevenue(
        int $orderId,
        int $tenantId,
        int $amount,
        string $channel,
        array $metadata = []
    ): array {
        return $this->withSpan(
            'supermarket_revenue.record_order',
            function () use ($orderId, $tenantId, $amount, $channel, $metadata) {
                // Fraud check
                $this->fraudControl->checkRevenue($tenantId, $amount);

                // TODO: Запись в БД
                $record = [
                    'order_id' => $orderId,
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'channel' => $channel,
                    'metadata' => $metadata,
                    'recorded_at' => now()->toIso8601String(),
                ];

                // Инвалидация кэша
                Cache::forget("supermarket:revenue:tenant:{$tenantId}:daily:" . now()->toDateString());
                Cache::forget("supermarket:revenue:tenant:{$tenantId}:monthly:" . now()->month);

                $this->logAction('revenue_recorded', null, [
                    'order_id' => $orderId,
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'channel' => $channel,
                ], null, $tenantId);

                return $record;
            },
            $this->getStandardAttributes('supermarket', 'revenue_record')
        );
    }

    /**
     * Получить выручку за период
     */
    public function getRevenueByPeriod(
        int $tenantId,
        ?int $businessGroupId = null,
        string $period = 'daily',
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_revenue.get_by_period',
            function () use ($tenantId, $businessGroupId, $period, $days) {
                $cacheKey = "supermarket:revenue:tenant:{$tenantId}:{$period}:{$days}";

                return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $businessGroupId, $period, $days) {
                    $startDate = now()->subDays($days);
                    $endDate = now();

                    $revenue = $this->calculateRevenue($tenantId, $businessGroupId, $startDate, $endDate);

                    return [
                        'period' => [
                            'start' => $startDate->toIso8601String(),
                            'end' => $endDate->toIso8601String(),
                            'type' => $period,
                            'days' => $days,
                        ],
                        'gross_revenue' => $revenue['gross'],
                        'refunds' => $revenue['refunds'],
                        'net_revenue' => $revenue['gross'] - $revenue['refunds'],
                        'commissions' => $revenue['commissions'],
                        'cashback' => $revenue['cashback'],
                        'profit' => $revenue['gross'] - $revenue['refunds'] - $revenue['commissions'] - $revenue['cashback'],
                        'by_channel' => $revenue['by_channel'],
                        'by_category' => $revenue['by_category'],
                        'average_daily' => round(($revenue['gross'] - $revenue['refunds']) / $days),
                        'trend' => $revenue['trend'],
                        'generated_at' => now()->toIso8601String(),
                    ];
                });
            },
            $this->getStandardAttributes('supermarket', 'revenue_get_period')
        );
    }

    /**
     * Рассчитать выручку
     */
    private function calculateRevenue(int $tenantId, ?int $businessGroupId, $startDate, $endDate): array
    {
        // TODO: Запрос к БД
        return [
            'gross' => 1500000,
            'refunds' => 150000,
            'commissions' => 75000,
            'cashback' => 30000,
            'by_channel' => [
                'app' => ['amount' => 900000, 'orders' => 720],
                'web' => ['amount' => 450000, 'orders' => 360],
                'api' => ['amount' => 150000, 'orders' => 120],
            ],
            'by_category' => [
                'Молочные' => ['amount' => 300000, 'margin' => 15],
                'Выпечка' => ['amount' => 200000, 'margin' => 25],
                'Овощи' => ['amount' => 250000, 'margin' => 20],
                'Мясо' => ['amount' => 400000, 'margin' => 18],
                'Бакалея' => ['amount' => 350000, 'margin' => 22],
            ],
            'trend' => [
                'direction' => 'increasing',
                'percentage' => 15.5,
            ],
        ];
    }

    /**
     * Получить выручку в реальном времени (сегодня)
     */
    public function getTodayRevenue(int $tenantId, ?int $businessGroupId = null): array
    {
        $cacheKey = "supermarket:revenue:tenant:{$tenantId}:today";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($tenantId, $businessGroupId) {
            // TODO: Запрос к БД
            return [
                'date' => now()->toDateString(),
                'gross_revenue' => 52000,
                'orders_count' => 42,
                'average_order_value' => 1238,
                'by_hour' => $this->getHourlyRevenue($tenantId, $businessGroupId),
            ];
        });
    }

    /**
     * Почасовая выручка
     */
    private function getHourlyRevenue(int $tenantId, ?int $businessGroupId): array
    {
        $hourly = [];
        for ($i = 0; $i < 24; $i++) {
            $hourly[sprintf('%02d:00', $i)] = [
                'revenue' => rand(0, 8000),
                'orders' => rand(0, 10),
            ];
        }
        return $hourly;
    }

    /**
     * Сравнить с прошлым периодом
     */
    public function compareWithPreviousPeriod(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30
    ): array {
        return $this->withSpan(
            'supermarket_revenue.compare_periods',
            function () use ($tenantId, $businessGroupId, $days) {
                $currentPeriod = $this->getRevenueByPeriod($tenantId, $businessGroupId, 'daily', $days);
                
                $previousStartDate = now()->subDays($days * 2);
                $previousEndDate = now()->subDays($days);
                $previousPeriod = $this->calculateRevenue($tenantId, $businessGroupId, $previousStartDate, $previousEndDate);

                $currentNet = $currentPeriod['net_revenue'];
                $previousNet = $previousPeriod['gross'] - $previousPeriod['refunds'];

                $growth = $previousNet > 0 ? (($currentNet - $previousNet) / $previousNet) * 100 : 0;

                return [
                    'current_period' => [
                        'start' => now()->subDays($days)->toIso8601String(),
                        'end' => now()->toIso8601String(),
                        'net_revenue' => $currentNet,
                    ],
                    'previous_period' => [
                        'start' => $previousStartDate->toIso8601String(),
                        'end' => $previousEndDate->toIso8601String(),
                        'net_revenue' => $previousNet,
                    ],
                    'growth_percentage' => round($growth, 2),
                    'growth_amount' => $currentNet - $previousNet,
                    'trend' => $growth > 0 ? 'positive' : ($growth < 0 ? 'negative' : 'neutral'),
                ];
            },
            $this->getStandardAttributes('supermarket', 'revenue_compare')
        );
    }

    /**
     * Получить топ каналов продаж
     */
    public function getTopChannels(
        int $tenantId,
        ?int $businessGroupId = null,
        int $days = 30,
        int $limit = 5
    ): array {
        $cacheKey = "supermarket:revenue:top_channels:{$tenantId}:{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($tenantId, $businessGroupId, $days, $limit) {
            $revenue = $this->calculateRevenue($tenantId, $businessGroupId, now()->subDays($days), now());
            $channels = $revenue['by_channel'];

            // Сортировка по выручке
            uasort($channels, function ($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });

            return array_slice($channels, 0, $limit, true);
        });
    }
}
