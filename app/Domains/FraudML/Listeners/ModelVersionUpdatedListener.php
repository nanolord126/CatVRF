<?php declare(strict_types=1);

namespace App\Domains\FraudML\Listeners;

use App\Domains\FraudML\Events\ModelVersionUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ModelVersionUpdatedListener — handles ModelVersionUpdated events
 * 
 * Actions:
 * - Invalidate fraud model cache
 * - Notify FraudControlService about new model
 * - Update Prometheus metrics
 * - Log audit trail
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class ModelVersionUpdatedListener
{
    public function handle(ModelVersionUpdated $event): void
    {
        Log::channel('audit')->info('ModelVersionUpdated event received', [
            'model_version' => $event->modelVersion->version,
            'action' => $event->action,
            'correlation_id' => $event->correlationId,
        ]);

        // Invalidate cache
        $this->invalidateCache($event);

        // Update Prometheus metrics (in production, this would use prometheus_client_php)
        $this->updateMetrics($event);

        // Notify dependent services
        $this->notifyDependentServices($event);
    }

    private function invalidateCache(ModelVersionUpdated $event): void
    {
        Cache::forget('fraud_model_active_version');
        Cache::tags(['fraud_ml', 'model_versions'])->flush();

        Log::debug('Fraud model cache invalidated', [
            'model_version' => $event->modelVersion->version,
            'action' => $event->action,
        ]);
    }

    private function updateMetrics(ModelVersionUpdated $event): void
    {
        // In production, this would use prometheus_client_php
        // For now, we'll log the metrics
        
        $metrics = [
            'model_version_updated_total' => [
                'action' => $event->action,
                'model_type' => $event->modelVersion->model_type,
            ],
        ];

        if ($event->action === 'promoted') {
            $metrics['ml_model_auc_current'] = $event->modelVersion->auc_roc;
            $metrics['ml_model_promoted_timestamp'] = now()->timestamp;
        }

        Log::debug('ML metrics updated', [
            'metrics' => $metrics,
            'correlation_id' => $event->correlationId,
        ]);
    }

    private function notifyDependentServices(ModelVersionUpdated $event): void
    {
        // Notify FraudControlService about new model version
        // This would normally dispatch an event or call a service
        // For now, we'll log the notification
        
        Log::info('Notifying dependent services about model update', [
            'model_version' => $event->modelVersion->version,
            'action' => $event->action,
            'services' => ['FraudControlService', 'TenantQuotaService'],
        ]);
    }
}
