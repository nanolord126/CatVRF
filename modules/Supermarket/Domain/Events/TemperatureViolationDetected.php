<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;
use Modules\Supermarket\Domain\Models\ProductTemperatureRequirement;

/**
 * TemperatureViolationDetected — Событие обнаружения нарушения температурного режима
 * 
 * Генерируется при обнаружении нарушения температурного режима
 * для последующей обработки (оповещения, синхронизация с CRM и т.д.)
 */
final class TemperatureViolationDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TemperatureReading $reading,
        public readonly TemperatureMonitoringDevice $device,
        public readonly ProductTemperatureRequirement $requirement,
    ) {}

    /**
     * Get the data to be sent to CRM
     */
    public function toCRMArray(): array
    {
        return [
            'event_type' => 'temperature_violation',
            'tenant_id' => $this->device->tenant_id,
            'timestamp' => $this->reading->recorded_at->toIso8601String(),
            'device' => [
                'device_id' => $this->device->device_id,
                'name' => $this->device->name,
                'location' => $this->device->location,
                'zone' => $this->device->zone,
                'manufacturer' => $this->device->manufacturer,
                'model' => $this->device->model,
            ],
            'reading' => [
                'reading_id' => $this->reading->id,
                'temperature_celsius' => $this->reading->temperature_celsius,
                'humidity_percent' => $this->reading->humidity_percent,
                'recorded_at' => $this->reading->recorded_at->toIso8601String(),
                'received_at' => $this->reading->received_at->toIso8601String(),
                'correlation_id' => $this->reading->correlation_id,
            ],
            'violation' => [
                'severity' => $this->reading->violation_severity,
                'is_violation' => $this->reading->is_violation,
                'requirement_id' => $this->requirement->id,
                'product_id' => $this->requirement->product_id,
                'min_temperature_celsius' => $this->requirement->min_temperature_celsius,
                'max_temperature_celsius' => $this->requirement->max_temperature_celsius,
                'optimal_temperature_celsius' => $this->requirement->optimal_temperature_celsius,
                'storage_type' => $this->requirement->storage_type,
                'is_hazardous' => $this->requirement->is_hazardous,
                'regulation_reference' => $this->requirement->regulation_reference,
            ],
            'metadata' => [
                'compliance_standard' => '152-ФЗ',
                'compliance_standard_fz323' => 'ФЗ-323',
                'alert_sent' => $this->reading->alert_sent,
                'alert_sent_at' => $this->reading->alert_sent_at?->toIso8601String(),
            ],
        ];
    }
}
