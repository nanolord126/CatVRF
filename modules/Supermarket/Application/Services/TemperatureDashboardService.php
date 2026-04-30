<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;
use Modules\Supermarket\Domain\Models\ProductTemperatureRequirement;
use Modules\Supermarket\Domain\Models\Document;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TemperatureDashboardService — Данные для дашборда температурного мониторинга
 * 
 * Агрегирует и предоставляет данные для дашборда температурного мониторинга
 * с кэшированием для быстрой загрузки
 */
final class TemperatureDashboardService
{
    use WithAuditLogging;
    use WithTelemetry;

    /**
     * Получить сводную статистику для дашборда
     */
    public function getDashboardSummary(?int $tenantId): array
    {
        $cacheKey = "temp:dashboard:summary:{$tenantId}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            return $this->withSpan(
                'temperature_dashboard.summary',
                function () use ($tenantId) {
                    $now = now();
                    $today = $now->copy()->startOfDay();
                    $weekAgo = $now->copy()->subWeek();

                    $query = TemperatureMonitoringDevice::query();
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }

                    $devices = $query->get();
                    $activeDevices = $devices->where('status', TemperatureMonitoringDevice::STATUS_ACTIVE);
                    $onlineDevices = $activeDevices->filter(fn($d) => $d->isOnline());

                    $readingsQuery = TemperatureReading::query();
                    if ($tenantId) {
                        $readingsQuery->where('tenant_id', $tenantId);
                    }

                    $todayReadings = (clone $readingsQuery)
                        ->where('recorded_at', '>=', $today)
                        ->get();

                    $violationsToday = (clone $readingsQuery)
                        ->where('is_violation', true)
                        ->where('recorded_at', '>=', $today)
                        ->get();

                    $criticalViolationsToday = $violationsToday
                        ->where('violation_severity', 'critical')
                        ->count();

                    $avgTemperatureToday = $todayReadings->avg('temperature_celsius');
                    $minTemperatureToday = $todayReadings->min('temperature_celsius');
                    $maxTemperatureToday = $todayReadings->max('temperature_celsius');

                    // Documents with temperature compliance
                    $documentsQuery = Document::query();
                    if ($tenantId) {
                        $documentsQuery->where('tenant_id', $tenantId);
                    }

                    $documentsWithTemp = (clone $documentsQuery)
                        ->where('requires_temperature_compliance', true)
                        ->get();

                    $compliantDocuments = $documentsWithTemp
                        ->where('temperature_compliance_status', 'compliant')
                        ->count();

                    $violationDocuments = $documentsWithTemp
                        ->where('temperature_compliance_status', 'violation')
                        ->count();

                    return [
                        'devices' => [
                            'total' => $devices->count(),
                            'active' => $activeDevices->count(),
                            'online' => $onlineDevices->count(),
                            'offline' => $activeDevices->count() - $onlineDevices->count(),
                            'calibration_due' => $activeDevices->filter(fn($d) => $d->isCalibrationDue())->count(),
                        ],
                        'readings' => [
                            'today_count' => $todayReadings->count(),
                            'avg_temperature_celsius' => round($avgTemperatureToday ?? 0, 2),
                            'min_temperature_celsius' => $minTemperatureToday,
                            'max_temperature_celsius' => $maxTemperatureToday,
                        ],
                        'violations' => [
                            'today_count' => $violationsToday->count(),
                            'critical_today' => $criticalViolationsToday,
                            'warning_today' => $violationsToday->where('violation_severity', 'warning')->count(),
                        ],
                        'documents' => [
                            'with_temperature_monitoring' => $documentsWithTemp->count(),
                            'compliant' => $compliantDocuments,
                            'violations' => $violationDocuments,
                            'warning' => $documentsWithTemp->where('temperature_compliance_status', 'warning')->count(),
                            'unknown' => $documentsWithTemp->where('temperature_compliance_status', 'unknown')->count(),
                        ],
                        'compliance_rate' => $documentsWithTemp->count() > 0 
                            ? round(($compliantDocuments / $documentsWithTemp->count()) * 100, 2)
                            : 0,
                        'generated_at' => $now->toIso8601String(),
                    ];
                },
                $this->getStandardAttributes(
                    vertical: 'supermarket',
                    operation: 'get_temperature_dashboard_summary',
                ),
            );
        });
    }

    /**
     * Получить данные для графика температуры
     */
    public function getTemperatureChart(?int $tenantId, ?int $deviceId, int $hours = 24): array
    {
        $cacheKey = "temp:dashboard:chart:{$tenantId}:{$deviceId}:{$hours}";
        
        return Cache::remember($cacheKey, 60, function () use ($tenantId, $deviceId, $hours) {
            return $this->withSpan(
                'temperature_dashboard.chart',
                function () use ($tenantId, $deviceId, $hours) {
                    $from = now()->subHours($hours);
                    $to = now();

                    $query = TemperatureReading::whereBetween('recorded_at', [$from, $to]);
                    
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }

                    if ($deviceId) {
                        $query->where('device_id', $deviceId);
                    }

                    $readings = $query->orderBy('recorded_at')->get();

                    // Group by hour
                    $grouped = $readings->groupBy(function ($reading) {
                        return $reading->recorded_at->format('Y-m-d H:00');
                    });

                    $chartData = $grouped->map(function ($hourReadings) {
                        return [
                            'timestamp' => $hourReadings->first()->recorded_at->toIso8601String(),
                            'avg_temperature' => round($hourReadings->avg('temperature_celsius'), 2),
                            'min_temperature' => $hourReadings->min('temperature_celsius'),
                            'max_temperature' => $hourReadings->max('temperature_celsius'),
                            'violations_count' => $hourReadings->where('is_violation', true)->count(),
                            'critical_violations' => $hourReadings->where('violation_severity', 'critical')->count(),
                            'readings_count' => $hourReadings->count(),
                        ];
                    })->values();

                    return [
                        'period' => [
                            'from' => $from->toIso8601String(),
                            'to' => $to->toIso8601String(),
                        ],
                        'data' => $chartData,
                        'generated_at' => now()->toIso8601String(),
                    ];
                },
                $this->getStandardAttributes(
                    vertical: 'supermarket',
                    operation: 'get_temperature_chart',
                ),
            );
        });
    }

    /**
     * Получить список устройств с их статусом
     */
    public function getDevicesList(?int $tenantId): array
    {
        $cacheKey = "temp:dashboard:devices:{$tenantId}";
        
        return Cache::remember($cacheKey, 60, function () use ($tenantId) {
            $query = TemperatureMonitoringDevice::with(['latestReading']);
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            $devices = $query->get();

            return $devices->map(function ($device) {
                $latestReading = $device->getLatestReading();
                
                return [
                    'id' => $device->id,
                    'device_id' => $device->device_id,
                    'name' => $device->name,
                    'location' => $device->location,
                    'zone' => $device->zone,
                    'status' => $device->status,
                    'is_online' => $device->isOnline(),
                    'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                    'calibration_due' => $device->isCalibrationDue(),
                    'next_calibration_due_at' => $device->next_calibration_due_at?->toIso8601String(),
                    'latest_reading' => $latestReading ? [
                        'temperature_celsius' => $latestReading->temperature_celsius,
                        'recorded_at' => $latestReading->recorded_at->toIso8601String(),
                        'is_violation' => $latestReading->is_violation,
                        'violation_severity' => $latestReading->violation_severity,
                    ] : null,
                ];
            })->toArray();
        });
    }

    /**
     * Получить недавние нарушения
     */
    public function getRecentViolations(?int $tenantId, int $limit = 20): array
    {
        $cacheKey = "temp:dashboard:violations:{$tenantId}:{$limit}";
        
        return Cache::remember($cacheKey, 30, function () use ($tenantId, $limit) {
            $query = TemperatureReading::with(['device', 'productRequirement'])
                ->where('is_violation', true)
                ->orderBy('recorded_at', 'desc')
                ->limit($limit);
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            $violations = $query->get();

            return $violations->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'device_name' => $violation->device->name,
                    'device_location' => $violation->device->location,
                    'temperature_celsius' => $violation->temperature_celsius,
                    'violation_severity' => $violation->violation_severity,
                    'recorded_at' => $violation->recorded_at->toIso8601String(),
                    'alert_sent' => $violation->alert_sent,
                    'alert_sent_at' => $violation->alert_sent_at?->toIso8601String(),
                    'product_name' => $violation->productRequirement?->product?->name,
                    'storage_type' => $violation->productRequirement?->storage_type,
                    'is_hazardous' => $violation->productRequirement?->is_hazardous ?? false,
                ];
            })->toArray();
        });
    }

    /**
     * Получить статистику по зонам хранения
     */
    public function getZoneStatistics(?int $tenantId): array
    {
        $cacheKey = "temp:dashboard:zones:{$tenantId}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $query = TemperatureMonitoringDevice::query();
            
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            $devices = $query->get();
            $groupedByZone = $devices->groupBy('zone');

            return $groupedByZone->map(function ($zoneDevices, $zone) {
                $onlineCount = $zoneDevices->filter(fn($d) => $d->isOnline())->count();
                
                // Get recent readings for this zone
                $deviceIds = $zoneDevices->pluck('id');
                $recentReadings = TemperatureReading::whereIn('device_id', $deviceIds)
                    ->where('recorded_at', '>=', now()->subHours(24))
                    ->get();

                $avgTemp = $recentReadings->avg('temperature_celsius');
                $violationsCount = $recentReadings->where('is_violation', true)->count();

                return [
                    'zone' => $zone,
                    'devices_count' => $zoneDevices->count(),
                    'online_devices' => $onlineCount,
                    'avg_temperature_celsius' => round($avgTemp ?? 0, 2),
                    'violations_24h' => $violationsCount,
                    'locations' => $zoneDevices->pluck('location')->unique()->toArray(),
                ];
            })->values()->toArray();
        });
    }

    /**
     * Получить данные для отчета по соответствию
     */
    public function getComplianceReport(?int $tenantId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $cacheKey = "temp:dashboard:compliance:{$tenantId}:{$from->toIso8601String()}:{$to->toIso8601String()}";
        
        return Cache::remember($cacheKey, 600, function () use ($tenantId, $from, $to) {
            return $this->withSpan(
                'temperature_dashboard.compliance_report',
                function () use ($tenantId, $from, $to) {
                    $readingsQuery = TemperatureReading::whereBetween('recorded_at', [$from, $to]);
                    
                    if ($tenantId) {
                        $readingsQuery->where('tenant_id', $tenantId);
                    }

                    $totalReadings = (clone $readingsQuery)->count();
                    $violations = (clone $readingsQuery)->where('is_violation', true)->get();
                    $criticalViolations = $violations->where('violation_severity', 'critical')->count();
                    $warningViolations = $violations->where('violation_severity', 'warning')->count();

                    // Group by device
                    $violationsByDevice = $violations->groupBy('device_id')->map(function ($deviceViolations) {
                        $device = $deviceViolations->first()->device;
                        return [
                            'device_name' => $device->name,
                            'device_location' => $device->location,
                            'violations_count' => $deviceViolations->count(),
                            'critical_count' => $deviceViolations->where('violation_severity', 'critical')->count(),
                            'avg_temperature' => round($deviceViolations->avg('temperature_celsius'), 2),
                        ];
                    });

                    // Documents compliance
                    $documentsQuery = Document::where('requires_temperature_compliance', true);
                    if ($tenantId) {
                        $documentsQuery->where('tenant_id', $tenantId);
                    }

                    $documents = $documentsQuery->get();
                    $compliantDocuments = $documents->where('temperature_compliance_status', 'compliant')->count();

                    return [
                        'period' => [
                            'from' => $from->toIso8601String(),
                            'to' => $to->toIso8601String(),
                        ],
                        'summary' => [
                            'total_readings' => $totalReadings,
                            'total_violations' => $violations->count(),
                            'critical_violations' => $criticalViolations,
                            'warning_violations' => $warningViolations,
                            'compliance_rate' => $totalReadings > 0 
                                ? round((($totalReadings - $violations->count()) / $totalReadings) * 100, 2)
                                : 100,
                        ],
                        'violations_by_device' => $violationsByDevice->values()->toArray(),
                        'documents_compliance' => [
                            'total_monitored' => $documents->count(),
                            'compliant' => $compliantDocuments,
                            'non_compliant' => $documents->count() - $compliantDocuments,
                            'compliance_rate' => $documents->count() > 0 
                                ? round(($compliantDocuments / $documents->count()) * 100, 2)
                                : 100,
                        ],
                        'generated_at' => now()->toIso8601String(),
                    ];
                },
                $this->getStandardAttributes(
                    vertical: 'supermarket',
                    operation: 'get_compliance_report',
                ),
            );
        });
    }
}
