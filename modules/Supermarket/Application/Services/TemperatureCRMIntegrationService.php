<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;
use Modules\Supermarket\Domain\Models\ProductTemperatureRequirement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * TemperatureCRMIntegrationService — Синхронизация температурных данных с CRM
 * 
 * Отправляет детальные данные о температурном мониторинге в CRM систему
 * для аналитики, отчетности и compliance tracking
 */
final class TemperatureCRMIntegrationService
{
    use WithAuditLogging;
    use WithTelemetry;

    private string $crmApiUrl;
    private string $crmApiKey;

    public function __construct()
    {
        $this->crmApiUrl = config('supermarket.crm_api_url', env('CRM_API_URL'));
        $this->crmApiKey = config('supermarket.crm_api_key', env('CRM_API_KEY'));
    }

    /**
     * Отправить данные о нарушении температурного режима в CRM
     */
    public function syncViolationToCRM(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device,
        ProductTemperatureRequirement $requirement
    ): bool {
        return $this->withSpan(
            'temperature_crm.sync_violation',
            function () use ($reading, $device, $requirement) {
                if (empty($this->crmApiUrl) || empty($this->crmApiKey)) {
                    Log::warning('CRM credentials not configured for temperature sync');
                    return false;
                }

                try {
                    $payload = [
                        'event_type' => 'temperature_violation',
                        'tenant_id' => $device->tenant_id,
                        'timestamp' => $reading->recorded_at->toIso8601String(),
                        'device' => [
                            'device_id' => $device->device_id,
                            'name' => $device->name,
                            'location' => $device->location,
                            'zone' => $device->zone,
                            'manufacturer' => $device->manufacturer,
                            'model' => $device->model,
                        ],
                        'reading' => [
                            'reading_id' => $reading->id,
                            'temperature_celsius' => $reading->temperature_celsius,
                            'humidity_percent' => $reading->humidity_percent,
                            'recorded_at' => $reading->recorded_at->toIso8601String(),
                            'received_at' => $reading->received_at->toIso8601String(),
                            'correlation_id' => $reading->correlation_id,
                        ],
                        'violation' => [
                            'severity' => $reading->violation_severity,
                            'is_violation' => $reading->is_violation,
                            'requirement_id' => $requirement->id,
                            'product_id' => $requirement->product_id,
                            'min_temperature_celsius' => $requirement->min_temperature_celsius,
                            'max_temperature_celsius' => $requirement->max_temperature_celsius,
                            'optimal_temperature_celsius' => $requirement->optimal_temperature_celsius,
                            'storage_type' => $requirement->storage_type,
                            'is_hazardous' => $requirement->is_hazardous,
                            'regulation_reference' => $requirement->regulation_reference,
                        ],
                        'metadata' => [
                            'compliance_standard' => '152-ФЗ',
                            'compliance_standard_fz323' => 'ФЗ-323',
                            'alert_sent' => $reading->alert_sent,
                            'alert_sent_at' => $reading->alert_sent_at?->toIso8601String(),
                        ],
                    ];

                    $response = Http::timeout(10)
                        ->retry(2, function ($attempt) {
                            return 1000 * $attempt;
                        })
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->crmApiKey,
                            'Content-Type' => 'application/json',
                            'X-Tenant-ID' => (string)$device->tenant_id,
                        ])
                        ->post($this->crmApiUrl . '/api/v1/temperature/violations', $payload);

                    if ($response->successful()) {
                        $this->logAction(
                            entity: 'temperature_violation_crm_sync',
                            entityId: $reading->id,
                            action: 'synced_to_crm',
                            context: [
                                'device_id' => $device->id,
                                'severity' => $reading->violation_severity,
                                'crm_response' => $response->json(),
                            ]
                        );

                        Log::info('Temperature violation synced to CRM', [
                            'reading_id' => $reading->id,
                            'device_id' => $device->id,
                            'severity' => $reading->violation_severity,
                        ]);

                        return true;
                    }

                    Log::error('Failed to sync temperature violation to CRM', [
                        'reading_id' => $reading->id,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                    return false;
                } catch (\Exception $e) {
                    $this->recordSpanException($e);

                    Log::error('Exception syncing temperature violation to CRM', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);

                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_temperature_violation_to_crm',
            ),
        );
    }

    /**
     * Отправить данные о температурном чтении в CRM (даже без нарушения)
     */
    public function syncReadingToCRM(
        TemperatureReading $reading,
        TemperatureMonitoringDevice $device
    ): bool {
        return $this->withSpan(
            'temperature_crm.sync_reading',
            function () use ($reading, $device) {
                if (empty($this->crmApiUrl) || empty($this->crmApiKey)) {
                    return false;
                }

                try {
                    $payload = [
                        'event_type' => 'temperature_reading',
                        'tenant_id' => $device->tenant_id,
                        'timestamp' => $reading->recorded_at->toIso8601String(),
                        'device' => [
                            'device_id' => $device->device_id,
                            'name' => $device->name,
                            'location' => $device->location,
                            'zone' => $device->zone,
                        ],
                        'reading' => [
                            'reading_id' => $reading->id,
                            'temperature_celsius' => $reading->temperature_celsius,
                            'humidity_percent' => $reading->humidity_percent,
                            'recorded_at' => $reading->recorded_at->toIso8601String(),
                            'received_at' => $reading->received_at->toIso8601String(),
                            'is_violation' => $reading->is_violation,
                            'violation_severity' => $reading->violation_severity,
                        ],
                    ];

                    $response = Http::timeout(5)
                        ->retry(1)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->crmApiKey,
                            'Content-Type' => 'application/json',
                            'X-Tenant-ID' => (string)$device->tenant_id,
                        ])
                        ->post($this->crmApiUrl . '/api/v1/temperature/readings', $payload);

                    return $response->successful();
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Exception syncing temperature reading to CRM', [
                        'reading_id' => $reading->id,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_temperature_reading_to_crm',
            ),
        );
    }

    /**
     * Отправить данные о регистрации устройства в CRM
     */
    public function syncDeviceToCRM(TemperatureMonitoringDevice $device): bool
    {
        return $this->withSpan(
            'temperature_crm.sync_device',
            function () use ($device) {
                if (empty($this->crmApiUrl) || empty($this->crmApiKey)) {
                    return false;
                }

                try {
                    $payload = [
                        'event_type' => 'device_registered',
                        'tenant_id' => $device->tenant_id,
                        'timestamp' => $device->created_at->toIso8601String(),
                        'device' => [
                            'device_id' => $device->id,
                            'manufacturer_device_id' => $device->device_id,
                            'name' => $device->name,
                            'serial_number' => $device->serial_number,
                            'manufacturer' => $device->manufacturer,
                            'model' => $device->model,
                            'location' => $device->location,
                            'zone' => $device->zone,
                            'coordinates' => $device->coordinates,
                            'reporting_interval_seconds' => $device->reporting_interval_seconds,
                            'accuracy_celsius' => $device->accuracy_celsius,
                            'status' => $device->status,
                        ],
                    ];

                    $response = Http::timeout(10)
                        ->retry(2)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->crmApiKey,
                            'Content-Type' => 'application/json',
                            'X-Tenant-ID' => (string)$device->tenant_id,
                        ])
                        ->post($this->crmApiUrl . '/api/v1/temperature/devices', $payload);

                    if ($response->successful()) {
                        $this->logAction(
                            entity: 'temperature_device_crm_sync',
                            entityId: $device->id,
                            action: 'synced_to_crm',
                            context: [
                                'device_id' => $device->device_id,
                                'name' => $device->name,
                            ]
                        );

                        Log::info('Temperature device synced to CRM', [
                            'device_id' => $device->id,
                            'device_identifier' => $device->device_id,
                        ]);

                        return true;
                    }

                    return false;
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Exception syncing device to CRM', [
                        'device_id' => $device->id,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_temperature_device_to_crm',
            ),
        );
    }

    /**
     * Отправить агрегированные данные о температурном мониторинге в CRM
     */
    public function syncAggregatedDataToCRM(int $tenantId, \Carbon\Carbon $from, \Carbon\Carbon $to): bool
    {
        return $this->withSpan(
            'temperature_crm.sync_aggregated',
            function () use ($tenantId, $from, $to) {
                if (empty($this->crmApiUrl) || empty($this->crmApiKey)) {
                    return false;
                }

                try {
                    // Get aggregated statistics
                    $devices = TemperatureMonitoringDevice::where('tenant_id', $tenantId)
                        ->active()
                        ->with(['readings' => function ($query) use ($from, $to) {
                            $query->whereBetween('recorded_at', [$from, $to]);
                        }])
                        ->get();

                    $aggregatedData = [
                        'event_type' => 'temperature_aggregated_report',
                        'tenant_id' => $tenantId,
                        'period' => [
                            'from' => $from->toIso8601String(),
                            'to' => $to->toIso8601String(),
                        ],
                        'summary' => [
                            'total_devices' => $devices->count(),
                            'active_devices' => $devices->where('status', 'active')->count(),
                            'total_readings' => $devices->sum(function ($device) {
                                return $device->readings->count();
                            }),
                            'total_violations' => $devices->sum(function ($device) {
                                return $device->readings->where('is_violation', true)->count();
                            }),
                            'critical_violations' => $devices->sum(function ($device) {
                                return $device->readings->where('violation_severity', 'critical')->count();
                            }),
                        ],
                        'devices' => $devices->map(function ($device) {
                            return [
                                'device_id' => $device->device_id,
                                'name' => $device->name,
                                'location' => $device->location,
                                'masked_customer_id' => $device->masked_customer_id ?? null,
                                'readings_count' => $device->readings->count(),
                                'violations_count' => $device->readings->where('is_violation', true)->count(),
                                'avg_temperature' => $device->readings->avg('temperature_celsius'),
                                'min_temperature' => $device->readings->min('temperature_celsius'),
                                'max_temperature' => $device->readings->max('temperature_celsius'),
                            ];
                        })->toArray(),
                    ];

                    $response = Http::timeout(30)
                        ->retry(2)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->crmApiKey,
                            'Content-Type' => 'application/json',
                            'X-Tenant-ID' => (string)$tenantId,
                        ])
                        ->post($this->crmApiUrl . '/api/v1/temperature/aggregated', $aggregatedData);

                    if ($response->successful()) {
                        $this->logAction(
                            entity: 'temperature_aggregated_crm_sync',
                            action: 'synced_to_crm',
                            context: [
                                'tenant_id' => $tenantId,
                                'period_from' => $from->toIso8601String(),
                                'period_to' => $to->toIso8601String(),
                            ]
                        );

                        Log::info('Temperature aggregated data synced to CRM', [
                            'tenant_id' => $tenantId,
                            'period' => $from->toIso8601String() . ' - ' . $to->toIso8601String(),
                        ]);

                        return true;
                    }

                    return false;
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Exception syncing aggregated data to CRM', [
                        'tenant_id' => $tenantId,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_aggregated_temperature_to_crm',
            ),
        );
    }

    /**
     * Отправить данные о соответствии документов температурному режиму в CRM
     */
    public function syncDocumentTemperatureComplianceToCRM(
        int $documentId,
        string $complianceStatus,
        ?int $violationCount = null
    ): bool {
        return $this->withSpan(
            'temperature_crm.sync_document_compliance',
            function () use ($documentId, $complianceStatus, $violationCount) {
                if (empty($this->crmApiUrl) || empty($this->crmApiKey)) {
                    return false;
                }

                try {
                    $payload = [
                        'event_type' => 'document_temperature_compliance',
                        'document_id' => $documentId,
                        'timestamp' => now()->toIso8601String(),
                        'compliance' => [
                            'status' => $complianceStatus,
                            'violation_count' => $violationCount,
                            'compliance_standard' => '152-ФЗ',
                            'compliance_standard_fz323' => 'ФЗ-323',
                        ],
                    ];

                    $response = Http::timeout(10)
                        ->retry(2)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $this->crmApiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post($this->crmApiUrl . '/api/v1/documents/temperature-compliance', $payload);

                    if ($response->successful()) {
                        $this->logAction(
                            entity: 'document_temperature_crm_sync',
                            entityId: $documentId,
                            action: 'synced_to_crm',
                            context: [
                                'compliance_status' => $complianceStatus,
                                'violation_count' => $violationCount,
                            ]
                        );

                        return true;
                    }

                    return false;
                } catch (\Exception $e) {
                    $this->recordSpanException($e);
                    Log::error('Exception syncing document compliance to CRM', [
                        'document_id' => $documentId,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_document_compliance_to_crm',
            ),
        );
    }
}
}
                    Log::error('Exception syncing document compliance to CRM', [
                        'document_id' => $documentId,
                        'error' => $e->getMessage(),
                    ]);
                    return false;
                }
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'sync_document_compliance_to_crm',
            ),
        );
    }
}
    }
}
