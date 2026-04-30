<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Events\ModelDriftDetected;

final class ModelDriftDetectedListener
{
    public function handle(ModelDriftDetected $event): void
    {
        try {
            Log::warning('Model drift detected', [
                'model_type' => $event->modelType,
                'model_version' => $event->modelVersion,
                'drift_status' => $event->driftStatus->value,
                'psi_value' => $event->psiValue,
                'accuracy_drop' => $event->accuracyDrop,
                'requires_retraining' => $event->requiresRetraining,
            ]);

            if ($event->requiresRetraining) {
                $this->triggerRetraining($event->modelType);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to handle model drift event', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function triggerRetraining(string $modelType): void
    {
        Log::info('Triggering model retraining', [
            'model_type' => $modelType,
            'reason' => 'drift_detected',
        ]);
    }
}
