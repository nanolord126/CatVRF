<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Supermarket\Domain\Models\TemperatureReading;
use Modules\Supermarket\Domain\Models\TemperatureMonitoringDevice;

/**
 * TemperatureReadingReceived — Событие получения температурного показания
 * 
 * Генерируется при получении любого температурного показания
 * для синхронизации с CRM и аналитики
 */
final class TemperatureReadingReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TemperatureReading $reading,
        public readonly TemperatureMonitoringDevice $device,
    ) {}

    /**
     * Get the data to be sent to CRM
     */
    public function toCRMArray(): array
    {
        return [
            'event_type' => 'temperature_reading',
            'tenant_id' => $this->device->tenant_id,
            'timestamp' => $this->reading->recorded_at->toIso8601String(),
            'device' => [
                'device_id' => $this->device->device_id,
                'name' => $this->device->name,
                'location' => $this->device->location,
                'zone' => $this->device->zone,
            ],
            'reading' => [
                'reading_id' => $this->reading->id,
                'temperature_celsius' => $this->reading->temperature_celsius,
                'humidity_percent' => $this->reading->humidity_percent,
                'recorded_at' => $this->reading->recorded_at->toIso8601String(),
                'received_at' => $this->reading->received_at->toIso8601String(),
                'is_violation' => $this->reading->is_violation,
                'violation_severity' => $this->reading->violation_severity,
            ],
        ];
    }
}
