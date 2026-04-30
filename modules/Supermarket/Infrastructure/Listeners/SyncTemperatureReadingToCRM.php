<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Listeners;

use Modules\Supermarket\Domain\Events\TemperatureReadingReceived;
use Modules\Supermarket\Application\Services\TemperatureCRMIntegrationService;
use Illuminate\Support\Facades\Log;

/**
 * SyncTemperatureReadingToCRM — Слушатель для синхронизации показаний с CRM
 * 
 * Отправляет данные о температурных показаниях в CRM систему для аналитики
 */
final class SyncTemperatureReadingToCRM
{
    public function __construct(
        private readonly TemperatureCRMIntegrationService $crmIntegration
    ) {}

    public function handle(TemperatureReadingReceived $event): void
    {
        try {
            $success = $this->crmIntegration->syncReadingToCRM(
                $event->reading,
                $event->device
            );

            if (!$success) {
                Log::warning('Failed to sync temperature reading to CRM', [
                    'reading_id' => $event->reading->id,
                    'device_id' => $event->device->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception in temperature reading CRM sync listener', [
                'reading_id' => $event->reading->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
