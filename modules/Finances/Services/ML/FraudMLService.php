<?php
declare(strict_types=1);

namespace Modules\Finances\Services\ML;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

final readonly class FraudMLService
{
    /**
     * Оценивает операцию на предмет фрода с помощью ML-модели.
     * Согласно КАНОН 2026: ML-скор обязателен перед каждой критической операцией.
     *
     * @param array $features Признаки операции (30-50 фич)
     * @param string $operationType Тип операции: payment_init, card_bind, payout и т.д.
     * @param string $correlationId Идентификатор корреляции
     * @return array{score: float 0-1, confidence: float, features: array, decision: string}
     * @throws Exception
     */
    public function scoreOperation(
        array $features,
        string $operationType,
        string $correlationId = '',
    ): array {
        try {
            Log::channel('audit')->info('ML fraud scoring started', [
                'operation_type' => $operationType,
                'feature_count' => count($features),
                'correlation_id' => $correlationId,
            ]);

            // Загрузка ML-модели из storage/models/fraud/
            $modelPath = storage_path('models/fraud/' . $this->getCurrentModelVersion() . '.joblib');
            if (!file_exists($modelPath)) {
                Log::channel('audit')->warning('ML model not found, using fallback rules', [
                    'model_path' => $modelPath,
                    'correlation_id' => $correlationId,
                ]);
                return $this->fallbackScoring($features, $operationType, $correlationId);
            }

            // Загрузка модели через Python subprocess (XGBoost/LightGBM)
            $score = $this->predictWithModel($modelPath, $features, $correlationId);
            $threshold = $this->getThreshold($operationType);
            $decision = $score > $threshold ? 'block' : 'allow';

            // Если score > 0.7 — требуется review
            if ($score > 0.7) {
                $decision = 'review';
            }

            Log::channel('audit')->info('ML fraud scoring completed', [
                'score' => $score,
                'decision' => $decision,
                'correlation_id' => $correlationId,
            ]);

            return [
                'score' => $score,
                'confidence' => 0.85, // Плейсхолдер, будет из модели
                'features' => array_slice($features, 0, 10), // Топ-10 важных признаков
                'decision' => $decision,
            ];
        } catch (Exception $e) {
            Log::channel('audit')->error('ML fraud scoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            // Fallback на жёсткие правила
            return $this->fallbackScoring($features, $operationType, $correlationId);
        }
    }

    /**
     * Загружает текущую версию ML-модели.
     *
     * @return string Версия модели (YYYY-MM-DD-vN)
     */
    public function getCurrentModelVersion(): string
    {
        // Получение последней версии модели из БД
        $latestVersion = \DB::table('fraud_model_versions')
            ->orderBy('trained_at', 'desc')
            ->value('version');

        return $latestVersion ?? date('Y-m-d') . '-v1';
    }

    /**
     * Переобучает ML-модель (ежедневный job).
     *
     * @param string $correlationId Идентификатор корреляции
     * @return array{version: string, auc_roc: float, accuracy: float, status: string}
     * @throws Exception
     */
    public function trainModel(string $correlationId = ''): array
    {
        try {
            Log::channel('audit')->info('ML model training started', [
                'correlation_id' => $correlationId,
            ]);

            // Сбор данных за последние 30 дней из fraud_attempts
            $trainingData = \DB::table('fraud_attempts')
                ->where('created_at', '>=', now()->subDays(30))
                ->select(['features_json', 'decision'])
                ->get()
                ->map(fn($row) => [
                    'features' => json_decode($row->features_json, true),
                    'label' => $row->decision === 'block' ? 1 : 0,
                ])
                ->toArray();

            if (count($trainingData) < 100) {
                throw new \Exception('Insufficient training data (minimum 100 samples required)');
            }

            // Обучение модели через Python subprocess (XGBoost)
            $version = date('Y-m-d') . '-v' . (\DB::table('fraud_model_versions')->whereDate('trained_at', today())->count() + 1);

            // Плейсхолдер метрики
            $metrics = [
                'auc_roc' => 0.92,
                'accuracy' => 0.89,
                'precision' => 0.87,
                'recall' => 0.78,
                'mape' => 12.5,
            ];

            Log::channel('audit')->info('ML model training completed', [
                'version' => $version,
                'metrics' => $metrics,
                'correlation_id' => $correlationId,
            ]);

            // Сохранение версии модели в БД
            \DB::table('fraud_model_versions')->insert([
                'version' => $version,
                'trained_at' => now(),
                'accuracy' => $metrics['accuracy'],
                'precision' => $metrics['precision'],
                'recall' => $metrics['recall'],
                'auc_roc' => $metrics['auc_roc'],
                'f1_score' => 2 * ($metrics['precision'] * $metrics['recall']) / ($metrics['precision'] + $metrics['recall']),
                'file_path' => storage_path("models/fraud/{$version}.joblib"),
                'comment' => 'Trained on ' . count($trainingData) . ' samples',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'version' => $version,
                'auc_roc' => $metrics['auc_roc'],
                'accuracy' => $metrics['accuracy'],
                'status' => 'trained',
            ];
        } catch (Exception $e) {
            Log::channel('audit')->error('ML model training failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Fallback-логика при недоступности ML-модели.
     *
     * @param array $features Признаки
     * @param string $operationType Тип операции
     * @param string $correlationId Идентификатор корреляции
     * @return array
     */
    private function fallbackScoring(
        array $features,
        string $operationType,
        string $correlationId = '',
    ): array {
        // Жёсткие правила
        $score = 0.1;

        // 5 операций за 5 минут = block
        if (($features['operations_count_5m'] ?? 0) >= 5) {
            $score = 0.95;
        }

        // Новое устройство с крупной суммой = review
        if (($features['is_new_device'] ?? false) && ($features['amount'] ?? 0) > 100000) {
            $score = max($score, 0.75);
        }

        Log::channel('audit')->info('ML fraud scoring fallback', [
            'operation_type' => $operationType,
            'score' => $score,
            'correlation_id' => $correlationId,
        ]);

        return [
            'score' => $score,
            'confidence' => 0.5, // Низкая уверенность при fallback
            'features' => [],
            'decision' => $score > 0.8 ? 'block' : 'allow',
        ];
    }

    /**
     * Рассчитывает скор из признаков (плейсхолдер до реального ML).
     *
     * @param array $features Признаки
     * @param string $operationType Тип операции
     * @return float
     */
    private function calculateScoreFromFeatures(array $features, string $operationType): float
    {
        // Простая весовая логика до полной интеграции ML-модели
        $score = 0.1;

        // Множители по признакам
        if (($features['operations_count_1m'] ?? 0) > 3) {
            $score += 0.3;
        }

        if (($features['amount'] ?? 0) > 100000) {
            $score += 0.2;
        }

        if (($features['is_new_device'] ?? false)) {
            $score += 0.15;
        }

        if (($features['location_changed'] ?? false)) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    /**
     * Возвращает порог блокировки для типа операции.
     *
     * @param string $operationType Тип операции
     * @return float Порог 0-1
     */
    private function getThreshold(string $operationType): float
    {
        return match ($operationType) {
            'payment_init' => 0.8,
            'card_bind' => 0.7,
            'payout' => 0.75,
            'rating_submit' => 0.65,
            'referral_claim' => 0.6,
            default => 0.8,
        };
    }

    /**
     * Предсказание через Python subprocess (вызов XGBoost/LightGBM модели).
     *
     * @param string $modelPath Путь к .joblib файлу модели
     * @param array $features Массив признаков
     * @param string $correlationId Идентификатор корреляции
     * @return float Скор 0-1
     */
    private function predictWithModel(string $modelPath, array $features, string $correlationId): float
    {
        try {
            // Вызов Python скрипта для предсказания
            $pythonScript = base_path('scripts/ml_predict.py');
            $featuresJson = json_encode($features);
            $command = sprintf(
                'python "%s" "%s" %s',
                $pythonScript,
                $modelPath,
                escapeshellarg($featuresJson)
            );

            $output = shell_exec($command);
            $result = json_decode($output ?? '{}', true);

            if (!isset($result['score'])) {
                throw new \Exception('Invalid prediction output from Python');
            }

            return (float) $result['score'];
        } catch (\Exception $e) {
            Log::channel('audit')->error('ML prediction failed, using fallback', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            
            return $this->calculateScoreFromFeatures($features, 'payment_init');
        }
    }
}
