<?php

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Models\{AdInteractionLog, AdPlacement, AdBanner};
use App\Domains\Advertising\Services\Security\AdShieldProtection;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * AdHeatmapService - Сервис для сбора и анализа тепловых карт кликов (Production 2026).
 * 
 * Отвечает за:
 * - Сбор координат кликов и впечатлений
 * - Fraud-detection через AdShield
 * - Построение тепловых карт (heatmaps)
 * - Аналитика эффективности зон баннеров
 */
class AdHeatmapService
{
    private string $correlationId;

    public function __construct(private AdShieldProtection $shield)
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Логирование взаимодействия (клик, впечатление) с fraud-detection.
     * 
     * Интеграция с JS на фронтенде для сбора координат кликов.
     * Все данные проверяются через AdShield на мошенничество перед записью в БД.
     *
     * @param array $data Данные взаимодействия:
     *   - banner_id (int): ID баннера
     *   - placement_id (int): ID плейсмента
     *   - event_type (string): 'click', 'impression', 'hover'
     *   - x (int, optional): X координата на экране
     *   - y (int, optional): Y координата на экране
     *   - viewport (string, optional): Размеры окна браузера
     *   - timestamp (int): Unix timestamp события
     * 
     * @return bool True если событие залогировано, false если заблокировано как fraud
     * 
     * @throws \Exception При критических ошибках
     */
    public function logInteraction(array $data): bool
    {
        try {
            // === Валидация входных данных ===
            if (empty($data['banner_id']) || empty($data['placement_id'])) {
                Log::warning('Invalid interaction data (missing IDs)', [
                    'data' => array_keys($data),
                    'correlation_id' => $this->correlationId,
                ]);

                return false;
            }

            $eventType = $data['event_type'] ?? 'click';
            $ip = request()->ip() ?? 'unknown';

            Log::debug('Processing interaction', [
                'banner_id' => $data['banner_id'],
                'event_type' => $eventType,
                'ip' => $ip,
                'correlation_id' => $this->correlationId,
            ]);

            // === Fraud-detection через AdShield ===
            if ($this->shield->detectFraud($data, $ip)) {
                Log::warning('Suspected fraud interaction detected', [
                    'banner_id' => $data['banner_id'],
                    'event_type' => $eventType,
                    'ip' => $ip,
                    'correlation_id' => $this->correlationId,
                ]);

                AuditLog::create([
                    'action' => 'advertising.fraud_interaction_detected',
                    'description' => "Подозрительное взаимодействие заблокировано",
                    'model_type' => 'AdInteractionLog',
                    'correlation_id' => $this->correlationId,
                    'metadata' => [
                        'banner_id' => $data['banner_id'],
                        'event_type' => $eventType,
                        'ip_hash' => hash('sha256', $ip),
                    ],
                ]);

                return false; // Блокируем запись мошеннического взаимодействия
            }

            // === Регистрация легального взаимодействия ===
            try {
                $interaction = AdInteractionLog::logInteraction(
                    \App\Domains\Advertising\Models\AdBanner::findOrFail($data['banner_id']),
                    \App\Domains\Advertising\Models\AdPlacement::findOrFail($data['placement_id']),
                    $eventType,
                    [
                        'point_x' => $data['x'] ?? 0,
                        'point_y' => $data['y'] ?? 0,
                        'ip_address' => $ip,
                        'user_agent' => request()->header('User-Agent'),
                        'referer' => request()->header('Referer'),
                        'metadata' => [
                            'viewport' => $data['viewport'] ?? 'unknown',
                            'timestamp' => $data['timestamp'] ?? now()->timestamp,
                        ],
                    ]
                );

                Log::debug('Interaction logged successfully', [
                    'interaction_id' => $interaction->id,
                    'banner_id' => $data['banner_id'],
                    'correlation_id' => $this->correlationId,
                ]);

                return true;

            } catch (Throwable $e) {
                Log::error('Failed to log interaction', [
                    'banner_id' => $data['banner_id'],
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                \Sentry\captureException($e);

                return false;
            }

        } catch (Throwable $e) {
            Log::error('Heatmap interaction processing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return false;
        }
    }

    /**
     * Аналитический отчет по эффективности зон баннера (Hot/Cold zones).
     * 
     * Анализирует распределение кликов по координатам баннера для оптимизации.
     *
     * @param AdPlacement $placement Анализируемый плейсмент
     * @param string $period Период анализа (7d, 30d, 90d)
     * 
     * @return array Структурированный отчет с горячими зонами
     */
    public function getEfficiencyReport(AdPlacement $placement, string $period = '7d'): array
    {
        try {
            // === Определение диапазона дат ===
            $daysBack = match($period) {
                '7d' => 7,
                '30d' => 30,
                '90d' => 90,
                default => 7,
            };

            $startDate = now()->subDays($daysBack);

            // === Сбор статистики кликов по зонам ===
            $interactions = AdInteractionLog::where('placement_id', $placement->id)
                ->where('event_type', 'click')
                ->where('is_fraud_suspected', false)
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw('ROUND(point_x / 50) * 50 as zone_x'),
                    DB::raw('ROUND(point_y / 50) * 50 as zone_y'),
                    DB::raw('COUNT(*) as click_count'),
                    DB::raw('AVG(fraud_score) as avg_fraud_score')
                )
                ->groupBy('zone_x', 'zone_y')
                ->orderByDesc('click_count')
                ->get();

            // === Расчет CTR и горячих зон ===
            $totalClicks = $interactions->sum('click_count');
            $totalImpressions = AdInteractionLog::where('placement_id', $placement->id)
                ->where('event_type', 'impression')
                ->where('created_at', '>=', $startDate)
                ->count();

            $ctr = $totalImpressions > 0
                ? ($totalClicks / $totalImpressions) * 100
                : 0;

            // === Формирование отчета ===
            return [
                'placement_id' => $placement->id,
                'placement_name' => $placement->name,
                'period' => $period,
                'date_range' => [
                    'from' => $startDate->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'metrics' => [
                    'total_clicks' => $totalClicks,
                    'total_impressions' => $totalImpressions,
                    'ctr' => round($ctr, 2),
                ],
                'hot_zones' => $interactions->take(10)->map(function($zone) use ($totalClicks) {
                    return [
                        'x' => (int) $zone->zone_x,
                        'y' => (int) $zone->zone_y,
                        'clicks' => $zone->click_count,
                        'percentage' => round(($zone->click_count / $totalClicks) * 100, 2),
                        'fraud_risk' => round($zone->avg_fraud_score, 2),
                    ];
                })->values()->all(),
                'generated_at' => now()->toDateTimeString(),
            ];

        } catch (Throwable $e) {
            Log::error('Failed to generate efficiency report', [
                'placement_id' => $placement->id,
                'error' => $e->getMessage(),
            ]);

            \Sentry\captureException($e);

            return [
                'placement_id' => $placement->id,
                'error' => 'Failed to generate report',
                'hot_zones' => [],
            ];
        }
    }
}
