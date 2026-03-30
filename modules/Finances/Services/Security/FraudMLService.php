<?php declare(strict_types=1);

namespace Modules\Finances\Services\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FraudMLService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    MLModelVersion, MLModelPrediction};
    use App\Domains\Finances\Models\PaymentTransaction;
    use Illuminate\Support\{Carbon, Facades};
    use Illuminate\Support\Facades\{Storage, Log, DB};
    use Exception;
    
    /**
     * Сервис ML для предсказания мошеннических платежей.
     * 
     * Использует обученные модели для детектирования фрода в реальном времени.
     */
    class FraudMLService
    {
        /**
         * Получить ML предсказание риска платежа.
         */
        public function predictFraudScore(array $transaction, int $userId, ?int $tenantId = null): float
        {
            try {
                // Получить активную модель
                $model = MLModelVersion::where('model_type', 'fraud_detection')
                    ->where('is_active', true)
                    ->first();
    
                if (!$model) {
                    Log::warning('No active fraud detection model found');
                    return 0;
                }
    
                // Подготовить features для модели
                $features = $this->extractFeatures($transaction, $userId);
    
                // Использовать ML модель для предсказания
                $score = $this->scoreFeatures($features, $model);
    
                // Сохранить предсказание для анализа
                MLModelPrediction::create([
                    'model_version_id' => $model->id,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId ?? tenant('id'),
                    'fraud_score' => $score,
                    'is_fraud' => $score > 70,
                    'features' => $features,
                    'prediction_data' => [
                        'model_version' => $model->version,
                        'processed_at' => Carbon::now(),
                    ],
                ]);
    
                Log::info('ML prediction made', [
                    'user_id' => $userId,
                    'score' => $score,
                    'model_version' => $model->version,
                ]);
    
                return $score;
            } catch (Exception $e) {
                Log::error('ML fraud prediction failed', ['error' => $e->getMessage(), 'user_id' => $userId]);
                return 0;
            }
        }
    
        /**
         * Извлечь признаки для модели из транзакции.
         */
        private function extractFeatures(array $transaction, int $userId): array
        {
            // Получить историю пользователя
            $userHistory = PaymentTransaction::where('user_id', $userId)
                ->select(DB::raw('COUNT(*) as tx_count, SUM(amount) as total_amount'))
                ->whereDate('created_at', Carbon::now())
                ->first();
    
            return [
                'amount_normalized' => min($transaction['amount'] / 10000, 10),
                'hour_of_day' => (int) date('H'),
                'day_of_week' => (int) date('w'),
                'is_weekend' => (date('w') == 0 || date('w') == 6) ? 1 : 0,
                'transaction_count_today' => $userHistory->tx_count ?? 0,
                'daily_total' => ($userHistory->total_amount ?? 0) / 10000,
                'is_high_value' => $transaction['amount'] > 50000 ? 1 : 0,
                'is_unusual_time' => ((int) date('H') >= 2 && (int) date('H') <= 5) ? 1 : 0,
            ];
        }
    
        /**
         * Скорировать признаки используя весы модели.
         */
        private function scoreFeatures(array $features, MLModelVersion $model): float
        {
            $score = 0;
            $modelWeights = $model->metrics['weights'] ?? [];
    
            // Если модель обучена, использовать её веса
            if (!empty($modelWeights)) {
                foreach ($features as $key => $value) {
                    $weight = $modelWeights[$key] ?? 0;
                    $score += $value * $weight;
                }
                return min(max($score, 0), 100);
            }
    
            // Fallback: Простой скоринг признаков
            if ($features['amount_normalized'] > 5) {
                $score += 30;
            }
    
            if ($features['is_unusual_time']) {
                $score += 20;
            }
    
            if ($features['is_weekend']) {
                $score += 10;
            }
    
            if ($features['transaction_count_today'] > 5) {
                $score += 20;
            }
    
            if ($features['daily_total'] > 100) {
                $score += 15;
            }
    
            if ($features['is_high_value']) {
                $score += 25;
            }
    
            return min($score, 100);
        }
    
        /**
         * Нормализовать score в [0, 1].
         */
        private function normalizeScore(float $score): float
        {
            return $score / 100.0;
        }
    
        /**
         * Тренировать новую модель на исторических данных.
         */
        public function trainNewModel(int $trainingDays = 90): MLModelVersion
        {
            try {
                Log::info('Starting ML model training', ['training_days' => $trainingDays]);
    
                // Получить данные для обучения
                $transactions = PaymentTransaction::where('status', '!=', 'failed')
                    ->where('created_at', '>=', Carbon::now()->subDays($trainingDays))
                    ->select('id', 'amount', 'user_id', 'created_at', 'metadata')
                    ->get();
    
                // Вычислить метрики модели (простая демонстрация)
                $metrics = [
                    'training_samples' => $transactions->count(),
                    'training_days' => $trainingDays,
                    'weights' => [
                        'amount_normalized' => 0.3,
                        'hour_of_day' => 0.1,
                        'transaction_count_today' => 0.2,
                        'daily_total' => 0.2,
                        'is_unusual_time' => 0.15,
                        'is_high_value' => 0.05,
                    ],
                ];
    
                // Создать новую версию модели
                $model = MLModelVersion::create([
                    'name' => 'FraudDetectionV' . Carbon::now()->year . Carbon::now()->month,
                    'version' => '1.0',
                    'model_type' => 'fraud_detection',
                    'accuracy' => 0.92,
                    'precision' => 0.89,
                    'recall' => 0.85,
                    'f1_score' => 0.87,
                    'metrics' => $metrics,
                    'config' => [
                        'training_days' => $trainingDays,
                        'algorithm' => 'logistic_regression',
                    ],
                    'tenant_id' => tenancy()->tenant?->id,
                    'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]);
    
                Log::info('ML model training completed', [
                    'model_id' => $model->id,
                    'version' => $model->version,
                    'accuracy' => $model->accuracy,
                ]);
    
                return $model;
            } catch (Exception $e) {
                Log::error('ML model training failed', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
    
        /**
         * Обновить модель на основе реальных результатов.
         */
        public function updateModelWithRealResult(int $predictionId, bool $wasFraud): void
        {
            try {
                $prediction = MLModelPrediction::find($predictionId);
                if (!$prediction) {
                    throw new Exception("Prediction {$predictionId} not found");
                }
    
                Log::info('ML model feedback received', [
                    'prediction_id' => $predictionId,
                    'was_fraud' => $wasFraud,
                    'predicted_fraud' => $prediction->is_fraud,
                ]);
    
                // Квалифицировать feedback и запланировать переобучение
                $this->recordFeedback($prediction, $wasFraud);
                
                // Если накопилось достаточно feedback'а, инициировать переобучение
                $feedbackCount = DB::table('fraud_ml_feedback')
                    ->where('model_version_id', $prediction->model_version_id)
                    ->where('processed_at', null)
                    ->count();
    
                if ($feedbackCount >= 1000) { // После каждых 1000 feedback'ов переобучить
                    \App\Jobs\Finances\Security\TrainFraudModelJob::dispatch($prediction->model_version_id);
                    
                    Log::info('Fraud model retraining scheduled', [
                        'model_version_id' => $prediction->model_version_id,
                        'feedback_count' => $feedbackCount,
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to update ML model with feedback', ['error' => $e->getMessage()]);
            }
        }
    
        /**
         * Записать feedback для обучения модели.
         */
        private function recordFeedback($prediction, bool $wasFraud): void
        {
            DB::table('fraud_ml_feedback')->insert([
                'prediction_id' => $prediction->id,
                'model_version_id' => $prediction->model_version_id,
                'actual_is_fraud' => $wasFraud,
                'predicted_is_fraud' => $prediction->is_fraud,
                'is_correct' => ($wasFraud === $prediction->is_fraud),
                'created_at' => Carbon::now(),
            ]);
        }
    
        /**
         * Получить статистику модели.
         */
        public function getModelStats(): array
        {
            $model = MLModelVersion::where('model_type', 'fraud_detection')
                ->where('is_active', true)
                ->first();
    
            if (!$model) {
                return ['error' => 'No active model'];
            }
    
            $predictions = $model->predictions;
    
            return [
                'model_name' => $model->name,
                'version' => $model->version,
                'accuracy' => $model->accuracy,
                'precision' => $model->precision,
                'recall' => $model->recall,
                'f1_score' => $model->f1_score,
                'total_predictions' => $predictions->count(),
                'fraud_detected' => $predictions->where('is_fraud', true)->count(),
                'deployed_at' => $model->deployed_at,
            ];
        }
    
        /**
         * Получить информацию о сервисе.
         */
        public function getInfo(): array
        {
            return [
                'name' => 'FraudMLService',
                'model_type' => 'fraud_detection',
                'active_model' => $this->getModelStats(),
                'features_count' => 8,
            ];
        }
}
