<?php

namespace App\Domains\Finances\Models\Security;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Carbon;
use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};

/**
 * Модель версий ML-модели для предсказания мошеннических платежей.
 *
 * Используется для A/B тестирования и отслеживания качества моделей обнаружения мошенничества.
 */
class MLModelVersion extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'ml_model_versions';
    protected $guarded = [];

    protected $casts = [
        'metrics' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'version',
        'model_type',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'metrics',
        'config',
        'is_active',
        'deployed_at',
        'correlation_id',
        'tenant_id',
    ];

    // Типы моделей
    public const TYPE_FRAUD_DETECTION = 'fraud_detection';
    public const TYPE_RISK_SCORING = 'risk_scoring';
    public const TYPE_CONVERSION_PREDICTION = 'conversion_prediction';

    /**
     * Предсказания, сделанные этой моделью.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(MLModelPrediction::class);
    }

    /**
     * Активировать эту версию модели.
     */
    public function activate(): bool
    {
        // Деактивировать все другие версии этого типа
        self::where('model_type', $this->model_type)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        return $this->update([
            'is_active' => true,
            'deployed_at' => Carbon::now(),
        ]);
    }

    /**
     * Получить метрику модели.
     */
    public function getMetric(string $name): ?float
    {
        return $this->metrics[$name] ?? null;
    }
}

/**
 * Модель для хранения предсказаний ML-модели.
 */
class MLModelPrediction extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'ml_model_predictions';
    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'confidence' => 'float',
        'is_fraud' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ml_model_version_id',
        'transaction_id',
        'user_id',
        'tenant_id',
        'is_fraud',
        'confidence',
        'features',
        'correlation_id',
    ];

    /**
     * Версия модели.
     */
    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(MLModelVersion::class);
    }
}
