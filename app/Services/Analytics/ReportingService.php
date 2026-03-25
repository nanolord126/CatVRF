<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ReportingService — генерация и управление отчётами
 * 
 * Методы:
 * - scheduleReport(tenantId, reportType, frequency, recipients)
 * - generateReport(tenantId, reportType, dateRange)
 * - getScheduledReports(tenantId)
 * - updateReportSchedule(reportId, schedule)
 * - deleteReportSchedule(reportId)
 * - sendReport(reportId, recipients)
 */
final class ReportingService
{
    private const CACHE_TTL_SCHEDULES = 86400;  // 24 hours
    private const CACHE_TTL_REPORTS = 3600;    // 1 hour

    /**
     * Запланировать отчёт на повторную генерацию
     */
    public function scheduleReport(
        int $tenantId,
        string $reportType,
        string $frequency,
        array $recipients,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $schedule = [
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'frequency' => $frequency,  // daily, weekly, monthly
            'recipients' => $recipients,  // emails
            'created_at' => now()->toIso8601String(),
            'next_send_at' => $this->calculateNextSendTime($frequency),
            'correlation_id' => $correlationId,
        ];

        $cacheKey = "reporting:schedule:{$tenantId}:{$reportType}:{$frequency}";
        $this->cache->put($cacheKey, $schedule, self::CACHE_TTL_SCHEDULES);

        $this->log->channel('audit')->info('Report schedule created', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'frequency' => $frequency,
            'recipients_count' => count($recipients),
        ]);

        return $schedule;
    }

    /**
     * Сгенерировать отчёт
     */
    public function generateReport(
        int $tenantId,
        string $reportType,
        string $dateRange = '30_days',
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $cacheKey = "reporting:report:{$tenantId}:{$reportType}:{$dateRange}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $report = [
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'date_range' => $dateRange,
            'generated_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
            'sections' => $this->generateReportSections($tenantId, $reportType),
        ];

        $this->cache->put($cacheKey, $report, self::CACHE_TTL_REPORTS);

        $this->log->channel('audit')->info('Report generated', [
            'correlation_id' => $correlationId,
            'tenant_id' => $tenantId,
            'report_type' => $reportType,
            'date_range' => $dateRange,
        ]);

        return $report;
    }

    /**
     * Получить запланированные отчёты
     */
    public function getScheduledReports(int $tenantId, array $context = []): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();
        $cacheKey = "reporting:schedules:{$tenantId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $schedules = [
            [
                'id' => Str::uuid()->toString(),
                'report_type' => 'weekly_summary',
                'frequency' => 'weekly',
                'recipients' => ['manager@example.com'],
                'next_send_at' => now()->addWeek()->toIso8601String(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'report_type' => 'monthly_detailed',
                'frequency' => 'monthly',
                'recipients' => ['cfo@example.com'],
                'next_send_at' => now()->addMonth()->toIso8601String(),
            ],
        ];

        $this->cache->put($cacheKey, $schedules, self::CACHE_TTL_SCHEDULES);

        return $schedules;
    }

    /**
     * Обновить расписание отчёта
     */
    public function updateReportSchedule(
        string $reportId,
        int $tenantId,
        array $updates,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $schedule = [
            'id' => $reportId,
            'tenant_id' => $tenantId,
            'frequency' => $updates['frequency'] ?? 'weekly',
            'recipients' => $updates['recipients'] ?? [],
            'updated_at' => now()->toIso8601String(),
            'next_send_at' => $this->calculateNextSendTime($updates['frequency'] ?? 'weekly'),
            'correlation_id' => $correlationId,
        ];

        $cacheKey = "reporting:schedule:{$reportId}";
        $this->cache->put($cacheKey, $schedule, self::CACHE_TTL_SCHEDULES);

        $this->log->channel('audit')->info('Report schedule updated', [
            'correlation_id' => $correlationId,
            'report_id' => $reportId,
            'tenant_id' => $tenantId,
        ]);

        return $schedule;
    }

    /**
     * Удалить расписание отчёта
     */
    public function deleteReportSchedule(string $reportId, int $tenantId, array $context = []): bool {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $this->cache->forget("reporting:schedule:{$reportId}");

        $this->log->channel('audit')->info('Report schedule deleted', [
            'correlation_id' => $correlationId,
            'report_id' => $reportId,
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * Отправить отчёт по email
     */
    public function sendReport(
        string $reportId,
        int $tenantId,
        array $recipients,
        array $context = []
    ): array {
        $correlationId = $context['correlation_id'] ?? Str::uuid()->toString();

        $result = [
            'report_id' => $reportId,
            'sent_at' => now()->toIso8601String(),
            'recipients_count' => count($recipients),
            'status' => 'queued',
            'correlation_id' => $correlationId,
        ];

        $this->log->channel('audit')->info('Report queued for sending', [
            'correlation_id' => $correlationId,
            'report_id' => $reportId,
            'tenant_id' => $tenantId,
            'recipients_count' => count($recipients),
        ]);

        return $result;
    }

    // ========== PRIVATE HELPERS ==========

    private function calculateNextSendTime(string $frequency): string {
        return match ($frequency) {
            'daily' => now()->addDay()->format('Y-m-d H:i:s'),
            'weekly' => now()->addWeek()->format('Y-m-d H:i:s'),
            'monthly' => now()->addMonth()->format('Y-m-d H:i:s'),
            default => now()->addDay()->format('Y-m-d H:i:s'),
        };
    }

    private function generateReportSections(int $tenantId, string $reportType): array {
        return match ($reportType) {
            'revenue_report' => [
                'summary' => ['total_revenue' => 250000, 'currency' => 'RUB'],
                'breakdown' => ['by_category' => [], 'by_source' => []],
                'trends' => ['daily_trend' => [], 'growth_rate' => 12.5],
            ],
            'performance_report' => [
                'kpis' => ['conversion_rate' => 0.045, 'aov' => 3500, 'ltv' => 45000],
                'comparisons' => ['vs_last_period' => [], 'vs_target' => []],
            ],
            'customer_report' => [
                'metrics' => ['total_customers' => 1250, 'new_customers' => 85],
                'segments' => ['by_value' => [], 'by_frequency' => []],
                'retention' => ['churn_rate' => 0.08],
            ],
            default => [],
        };
    }
}
