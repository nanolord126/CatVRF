<?php declare(strict_types=1);

namespace App\Providers\Prometheus;

use Spatie\Prometheus\CollectorInterface;
use Spatie\Prometheus\Facades\Prometheus;
use App\Models\FraudModelVersion;

/**
 * MLMetricsCollector — ML model metrics collector for Prometheus
 * 
 * Exports ML model-related metrics:
 * - Current active model version
 * - Model AUC/ROC scores
 * - Model training timestamps
 * - Shadow mode status
 * 
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class MLMetricsCollector implements CollectorInterface
{
    public function register(): void
    {
        Prometheus::addGauge()
            ->name('catvrf_ml_model_active')
            ->help('Whether the model version is active (1) or not (0)')
            ->label('model_version', 'model_type');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_auc_current')
            ->help('Current ML model AUC score')
            ->label('model_version', 'model_type');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_precision_current')
            ->help('Current ML model precision')
            ->label('model_version', 'model_type');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_recall_current')
            ->help('Current ML model recall')
            ->label('model_version', 'model_type');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_f1_current')
            ->help('Current ML model F1 score')
            ->label('model_version', 'model_type');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_shadow_mode')
            ->help('Whether the model is in shadow mode (1) or not (0)')
            ->label('model_version');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_shadow_predictions_count')
            ->help('Number of predictions made in shadow mode')
            ->label('model_version');

        Prometheus::addGauge()
            ->name('catvrf_ml_model_trained_at_timestamp')
            ->help('Timestamp when the model was trained')
            ->label('model_version');
    }

    public function collect(): void
    {
        $models = FraudModelVersion::all();

        foreach ($models as $model) {
            $modelVersion = $this->sanitizeLabel($model->version);
            $modelType = $this->sanitizeLabel($model->model_type);

            // Active status
            Prometheus::addGauge()
                ->name('catvrf_ml_model_active')
                ->label('model_version', $modelVersion)
                ->label('model_type', $modelType)
                ->set($model->is_active ? 1 : 0);

            // AUC score
            if ($model->auc_roc !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_auc_current')
                    ->label('model_version', $modelVersion)
                    ->label('model_type', $modelType)
                    ->set($model->auc_roc);
            }

            // Precision
            if ($model->precision !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_precision_current')
                    ->label('model_version', $modelVersion)
                    ->label('model_type', $modelType)
                    ->set($model->precision);
            }

            // Recall
            if ($model->recall !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_recall_current')
                    ->label('model_version', $modelVersion)
                    ->label('model_type', $modelType)
                    ->set($model->recall);
            }

            // F1 score
            if ($model->f1_score !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_f1_current')
                    ->label('model_version', $modelVersion)
                    ->label('model_type', $modelType)
                    ->set($model->f1_score);
            }

            // Shadow mode status
            Prometheus::addGauge()
                ->name('catvrf_ml_model_shadow_mode')
                ->label('model_version', $modelVersion)
                ->set($model->is_shadow ? 1 : 0);

            // Shadow predictions count
            if ($model->shadow_predictions_count !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_shadow_predictions_count')
                    ->label('model_version', $modelVersion)
                    ->set($model->shadow_predictions_count);
            }

            // Training timestamp
            if ($model->trained_at !== null) {
                Prometheus::addGauge()
                    ->name('catvrf_ml_model_trained_at_timestamp')
                    ->label('model_version', $modelVersion)
                    ->set($model->trained_at->timestamp);
            }
        }
    }

    private function sanitizeLabel(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', substr($value, 0, 50));
    }
}
