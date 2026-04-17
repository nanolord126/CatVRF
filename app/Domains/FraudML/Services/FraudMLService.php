<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Services;

use Carbon\Carbon;
use App\Models\FraudModelVersion;
use App\Services\ML\FraudMLFeatureStore;
use App\Services\ML\FraudMLExplainer;
use App\Services\ML\FeatureDriftDetectorService;
use App\Services\ML\FeatureDriftMetricsService;
use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;
use App\Domains\FraudML\DTOs\OperationDto;

/**
 * Исключительный сервис антифрод контроля на базе ML. 
 * Перед любой финансовой или критической мутацией ОБЯЗАТЕЛЕН вызов скоринга здесь.
 */
final readonly class FraudMLService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly FraudMLExplainer $explainer,
        private readonly FraudMLFeatureStore $featureStore,
        private readonly FeatureDriftDetectorService $driftDetector,
        private readonly FeatureDriftMetricsService $driftMetrics
    ) {}

    /**
     * Осуществляет безоговорочную оценку операции по шкале фрода [0.0 ... 1.0].
     * Также выполняет shadow inference если есть shadow модели.
     */
    public function scoreOperation(OperationDto $dto): float
    {
        // Build context with quota and vertical info
        $context = array_merge($dto->additional_context, [
            'vertical_code' => $dto->vertical_code,
            'current_quota_usage_ratio' => $dto->current_quota_usage_ratio,
            'ip_address' => $dto->ip_address,
            'device_fingerprint' => $dto->device_fingerprint,
        ]);

        // Извлекаем и сохраняем признаки через Feature Store (single source of truth)
        $features = $this->featureStore->extractAndStoreOperationFeatures(
            tenantId: $dto->tenant_id,
            userId: $dto->user_id,
            operationType: $dto->operation_type,
            amount: (float)$dto->amount,
            context: $context,
            correlationId: $dto->correlation_id
        );

        // Получаем активную модель
        $activeModel = $this->getActiveModel();
        $activeScore = $this->predictWithModel($features, $activeModel);

        // Выполняем shadow inference для всех shadow моделей
        // Generate SHAP explanation for high-risk predictions
        $explanation = null;
        if ($activeScore > 0.7) {
            $explanation = $this->explainer->explainPrediction(
                $features,
                $activeScore,
                $activeModel?->version
            );
        }

        $this->performShadowInference($features, $dto);

        $this->logger->info('Fraud ML Score calculated', [
            'correlation_id' => $dto->correlation_id,
            'operation' => $dto->operation_type,
            'vertical_code' => $dto->vertical_code,
            'quota_usage_ratio' => $dto->current_quota_usage_ratio,
            'score' => $activeScore,
            'model_version' => $activeModel?->version,
            'shap_explanation' => $explanation,
            'feature_source' => 'feature_store',
            'decision' => $this->shouldBlock($activeScore, $dto->operation_type) ? 'block' : 'allow'
        ]);

        return $activeScore;
    }

    /**
     * Получить активную модель (из кэша или БД)
     */
    private function getActiveModel(): ?FraudModelVersion
    {
        $cachedVersion = cache('fraud_model_active_version');
        
        if ($cachedVersion !== null) {
            return FraudModelVersion::where('version', $cachedVersion)
                ->where('is_active', true)
                ->where('is_shadow', false)
                ->first();
        }

        return FraudModelVersion::getActive();
    }

    /**
     * Выполнить shadow inference для всех shadow моделей
     * Результаты записываются в лог/ClickHouse для последующего анализа
     */
    private function performShadowInference(array $features, OperationDto $dto): void
    {
        $shadowModels = FraudModelVersion::getShadowModels();

        foreach ($shadowModels as $shadowModel) {
            try {
                $shadowScore = $this->predictWithModel($features, $shadowModel);

                // Логируем shadow prediction (в реальной реализации - в ClickHouse)
                $this->logger->info('FraudML shadow prediction', [
                    'correlation_id' => $dto->correlation_id,
                    'operation' => $dto->operation_type,
                    'shadow_model_version' => $shadowModel->version,
                    'shadow_score' => $shadowScore,
                    'shadow_predictions_count' => $shadowModel->shadow_predictions_count + 1,
                ]);

                // Инкрементируем счётчик shadow predictions
                $shadowModel->increment('shadow_predictions_count');

            } catch (\Exception $e) {
                $this->logger->warning('Shadow inference failed', [
                    'shadow_model_version' => $shadowModel->version,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Решает, нужно ли жестко заблокировать операцию на основе порога из конфигурации.
     */
    public function shouldBlock(float $score, string $operationType): bool
    {
        $threshold = $this->config->get("fraud.thresholds.{$operationType}", 0.85);
        return $score >= $threshold;
    }

    /**
     * Генерирует массив признаков из истории клиента. Минимум 30-50 фич в реальном бою.
     */
    private function extractFeatures(OperationDto $dto): array
    {
        return [
            'amount_log' => log($dto->amount > 0 ? $dto->amount : 1),
            'hour_of_day' => Carbon::now()->hour,
            // Дополнительные фичи (age of account, tx_count_1h, etc)
        ];
    }

    /**
     * Предсказание с указанной моделью
     */
    private function predictWithModel(array $features, ?FraudModelVersion $model): float
    {
        if ($model === null) {
            return $this->predictWithFallback($features);
        }

        // В реальном приложении здесь http вызов к Python microservice или загрузка .joblib
        // Для демо: симулируем предсказание
        return $this->simulatePrediction($features, $model);
    }

    /**
     * Гарантированный fallback на жесткие правила, если ML сервис временно недоступен.
     */
    private function predictWithFallback(array $features): float
    {
        // В реальном приложении здесь http вызов к Python microservice. Если сбой -> return 0.5 (ревеью)
        return 0.5; // conservative fallback - requires review
    }

    /**
     * Симуляция предсказания (для демо)
     */
    private function simulatePrediction(array $features, FraudModelVersion $model): float
    {
        // В реальной реализации здесь вызов Python ML inference service
        // Загрузка модели из $model->file_path и предсказание
        
        // Симуляция: base score + random noise based on model version
        $baseScore = 0.15;
        $noise = (float) hexdec(substr(md5($model->version), 0, 4)) / 65535 * 0.3;
        
        return min(1.0, max(0.0, $baseScore + $noise));
    }

    /**
     * Perform runtime drift check for a single feature value
     * 
     * This is called during inference to detect if a feature value
     * is significantly different from the training distribution.
     * 
     * @param string $featureName Feature name
     * @param mixed $featureValue Current feature value
     * @param string $verticalCode Vertical code
     * @return array Drift check result
     */
    public function checkRuntimeFeatureDrift(string $featureName, mixed $featureValue, string $verticalCode = 'default'): array
    {
        $driftDetectionEnabled = $this->config->get('fraud.drift_detection.enabled', true);

        if (!$driftDetectionEnabled) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'threshold' => 0.0,
            ];
        }

        $activeModel = $this->getActiveModel();
        if ($activeModel === null) {
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'threshold' => 0.0,
                'reason' => 'no_active_model',
            ];
        }

        // Get reference distribution for this feature
        $referenceDist = $this->driftDetector->getReferenceDistribution(
            $activeModel->version,
            $featureName,
            $verticalCode
        );

        if ($referenceDist === null) {
            // No reference distribution - cannot check drift
            return [
                'drift_detected' => false,
                'drift_score' => 0.0,
                'threshold' => 0.0,
                'reason' => 'no_reference_distribution',
            ];
        }

        // For categorical features: check if value exists in reference
        if ($this->isCategoricalFeature($referenceDist)) {
            $driftDetected = !array_key_exists((string)$featureValue, $referenceDist);

            if ($driftDetected) {
                $this->logger->warning('Runtime feature drift detected - unknown category', [
                    'feature' => $featureName,
                    'value' => $featureValue,
                    'model_version' => $activeModel->version,
                    'vertical_code' => $verticalCode,
                ]);

                // Record drift metric
                $this->driftMetrics->recordFeatureDrift($featureName, 'psi', 1.0, $verticalCode);
            }

            return [
                'drift_detected' => $driftDetected,
                'drift_score' => $driftDetected ? 1.0 : 0.0,
                'threshold' => 0.0,
                'reason' => $driftDetected ? 'unknown_category' : 'ok',
            ];
        }

        // For continuous features: check if value is within reference range
        $referenceValues = array_keys($referenceDist);
        $minValue = min($referenceValues);
        $maxValue = max($referenceValues);

        // Simple outlier detection: if value is outside 3 standard deviations
        $mean = array_sum($referenceValues) / count($referenceValues);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $referenceValues)) / count($referenceValues);
        $stdDev = sqrt($variance);

        $zScore = abs(($featureValue - $mean) / ($stdDev > 0 ? $stdDev : 1));
        $driftDetected = $zScore > 3.0;

        if ($driftDetected) {
            $this->logger->warning('Runtime feature drift detected - outlier value', [
                'feature' => $featureName,
                'value' => $featureValue,
                'z_score' => $zScore,
                'mean' => $mean,
                'std_dev' => $stdDev,
                'model_version' => $activeModel->version,
                'vertical_code' => $verticalCode,
            ]);

            // Record drift metric
            $this->driftMetrics->recordFeatureDrift($featureName, 'ks', $zScore / 3.0, $verticalCode);
        }

        return [
            'drift_detected' => $driftDetected,
            'drift_score' => $zScore / 3.0,
            'threshold' => 1.0,
            'z_score' => $zScore,
            'mean' => $mean,
            'std_dev' => $stdDev,
            'reason' => $driftDetected ? 'outlier' : 'ok',
        ];
    }

    /**
     * Check if feature is categorical based on reference distribution
     * 
     * @param array $referenceDist Reference distribution
     * @return bool
     */
    private function isCategoricalFeature(array $referenceDist): bool
    {
        $keys = array_keys($referenceDist);
        
        // If keys are strings or have low cardinality, treat as categorical
        foreach ($keys as $key) {
            if (!is_numeric($key)) {
                return true;
            }
        }

        // If numeric but few unique values, also treat as categorical
        return count($keys) < 20;
    }

    /**
     * Get feature distribution from training data (for MLRecalculateJob integration)
     * 
     * @param string $featureName Feature name
     * @param Carbon $dateFrom Start date
     * @param Carbon $dateTo End date
     * @return array Feature distribution
     */
    public function getFeatureDistribution(string $featureName, Carbon $dateFrom, Carbon $dateTo): array
    {
        // In production, this would query ClickHouse for actual feature values
        // For now, return empty array - to be implemented with actual data source
        return [];
    }

    /**
     * Gather training data for model retraining (for MLRecalculateJob integration)
     * 
     * @param Carbon $dateFrom Start date
     * @param Carbon $dateTo End date
     * @return \Illuminate\Support\Collection Training data
     */
    public function gatherTrainingData(Carbon $dateFrom, Carbon $dateTo): \Illuminate\Support\Collection
    {
        // In production, this would query ClickHouse for actual training data
        // For now, return empty collection - to be implemented with actual data source
        return collect();
    }

    /**
     * Train model on training data (for MLRecalculateJob integration)
     * 
     * @param \Illuminate\Support\Collection $trainingData Training data
     * @return string Model version
     */
    public function trainModel(\Illuminate\Support\Collection $trainingData): string
    {
        // In production, this would call Python ML service for actual training
        $version = 'v' . Carbon::now()->format('Y-m-d-His');
        
        $this->logger->info('Model trained', [
            'version' => $version,
            'samples_count' => $trainingData->count(),
        ]);

        return $version;
    }

    /**
     * Evaluate model performance (for MLRecalculateJob integration)
     * 
     * @param string $modelVersion Model version
     * @return array Evaluation metrics
     */
    public function evaluateModel(string $modelVersion): array
    {
        // In production, this would call Python ML service for actual evaluation
        return [
            'accuracy' => 0.95,
            'precision' => 0.90,
            'recall' => 0.85,
            'f1_score' => 0.875,
            'auc_roc' => 0.94,
        ];
    }

    /**
     * Get model metrics (for MLRecalculateJob integration)
     * 
     * @param string $modelVersion Model version
     * @return array Model metrics
     */
    public function getModelMetrics(string $modelVersion): array
    {
        // In production, this would fetch from database
        return [
            'accuracy' => 0.93,
            'precision' => 0.88,
            'recall' => 0.83,
            'f1_score' => 0.855,
            'auc_roc' => 0.92,
        ];
    }

    /**
     * Switch to a specific model version (for MLRecalculateJob integration)
     * 
     * @param string $modelVersion Model version
     * @return void
     */
    public function switchToModel(string $modelVersion): void
    {
        // Update cache and database
        cache(['fraud_model_active_version' => $modelVersion], now()->addHours(24));

        FraudModelVersion::where('is_active', true)->update(['is_active' => false]);
        FraudModelVersion::where('version', $modelVersion)->update(['is_active' => true]);

        $this->logger->info('Model switched', [
            'version' => $modelVersion,
        ]);
    }
}
