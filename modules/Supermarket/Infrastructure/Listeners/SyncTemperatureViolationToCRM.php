<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Listeners;

use Modules\Supermarket\Domain\Events\TemperatureViolationDetected;
use Modules\Supermarket\Application\Services\TemperatureCRMIntegrationService;
use Illuminate\Support\Facades\Log;

/**
 * SyncTemperatureViolationToCRM — Слушатель для синхронизации нарушений с CRM
 * 
 * Отправляет детальные данные о нарушении температурного режима в CRM систему
 */
final class SyncTemperatureViolationToCRM
{
    public function __construct(
        private readonly TemperatureCRMIntegrationService $crmIntegration
    ) {}

    public function handle(TemperatureViolationDetected $event): void
    {
        try {
            $success = $this->crmIntegration->syncViolationToCRM(
                $event->reading,
                $event->device,
                $event->requirement
            );

            if (!$success) {
                Log::warning('Failed to sync temperature violation to CRM', [
                    'reading_id' => $event->reading->id,
                    'device_id' => $event->device->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception in temperature violation CRM sync listener', [
                'reading_id' => $event->reading->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
