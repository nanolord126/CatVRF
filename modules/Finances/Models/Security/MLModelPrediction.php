<?php declare(strict_types=1);

namespace Modules\Finances\Models\Security;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MLModelPrediction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    HasEcosystemFeatures, HasEcosystemAuth};
    use App\Domains\Finances\Models\PaymentTransaction;
    
    /**
     * Модель для хранения предсказаний ML-модели.
     * 
     * Отслеживает предсказания фродов, используется для:
     * - A/B тестирования моделей
     * - Обучения на реальных результатах
     * - Анализа качества предсказаний
     */
    class MLModelPrediction extends Model
    {
        use HasEcosystemFeatures, HasEcosystemAuth;
    
        protected $table = 'ml_model_predictions';
        protected $guarded = [];
    
        protected $casts = [
            'fraud_score' => 'float',
            'features' => 'array',
            'prediction_data' => 'array',
            'is_accurate' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    
        protected $fillable = [
            'model_version_id',
            'payment_transaction_id',
            'user_id',
            'fraud_score',
            'is_fraud',
            'features',
            'prediction_data',
            'actual_result',
            'is_accurate',
            'correlation_id',
            'tenant_id',
        ];
    
        /**
         * Модель ML версии.
         */
        public function modelVersion(): BelongsTo
        {
            return $this->belongsTo(MLModelVersion::class);
        }
    
        /**
         * Транзакция платежа.
         */
        public function paymentTransaction(): BelongsTo
        {
            return $this->belongsTo(PaymentTransaction::class);
        }
    
        /**
         * Область видимости: по тенанту.
         */
        public function scopeByTenant($query, ?int $tenantId = null)
        {
            return $query->where('tenant_id', $tenantId ?? tenant('id'));
        }
    
        /**
         * Область видимости: по модели.
         */
        public function scopeByModel($query, int $modelVersionId)
        {
            return $query->where('model_version_id', $modelVersionId);
        }
    
        /**
         * Область видимости: точные предсказания.
         */
        public function scopeAccurate($query)
        {
            return $query->where('is_accurate', true);
        }
    
        /**
         * Область видимости: неточные предсказания (для переобучения).
         */
        public function scopeInaccurate($query)
        {
            return $query->where('is_accurate', false);
        }
}
