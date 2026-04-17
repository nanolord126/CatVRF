<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FraudModelVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'version',
        'model_type',
        'trained_at',
        'shadow_started_at',
        'promoted_at',
        'is_shadow',
        'is_active',
        'is_rollback_candidate',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'auc_roc',
        'shadow_auc_roc',
        'shadow_predictions_count',
        'shadow_drift_score',
        'file_path',
        'file_hash',
        'is_encrypted',
        'feature_importance',
        'training_metadata',
        'comment',
        'trained_by',
        'correlation_id',
        'rolled_back_from_id',
        'rollback_reason',
    ];

    protected $casts = [
        'trained_at' => 'datetime',
        'shadow_started_at' => 'datetime',
        'promoted_at' => 'datetime',
        'is_shadow' => 'boolean',
        'is_active' => 'boolean',
        'is_rollback_candidate' => 'boolean',
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'auc_roc' => 'decimal:4',
        'shadow_auc_roc' => 'decimal:4',
        'shadow_predictions_count' => 'integer',
        'shadow_drift_score' => 'integer',
        'is_encrypted' => 'boolean',
        'feature_importance' => 'array',
        'training_metadata' => 'array',
    ];

    /**
     * Relationship to the model this was rolled back from
     */
    public function rolledBackFrom(): BelongsTo
    {
        return $this->belongsTo(FraudModelVersion::class, 'rolled_back_from_id');
    }

    /**
     * Get the currently active model
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)
            ->where('is_shadow', false)
            ->latest('promoted_at')
            ->first();
    }

    /**
     * Get models currently in shadow mode
     */
    public static function getShadowModels(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_shadow', true)
            ->where('is_active', false)
            ->orderBy('shadow_started_at', 'desc')
            ->get();
    }

    /**
     * Check if model is ready for promotion (24h shadow period + quality check)
     */
    public function isReadyForPromotion(): bool
    {
        if (!$this->is_shadow) {
            return false;
        }

        if ($this->shadow_started_at === null) {
            return false;
        }

        // Must be in shadow for at least 24 hours
        if ($this->shadow_started_at->diffInHours(now()) < 24) {
            return false;
        }

        // Must have shadow metrics
        if ($this->shadow_auc_roc === null || $this->shadow_predictions_count < 100) {
            return false;
        }

        // Quality threshold
        if ($this->shadow_auc_roc < 0.92) {
            return false;
        }

        return true;
    }

    /**
     * Mark model as shadow mode
     */
    public function startShadowMode(): void
    {
        $this->is_shadow = true;
        $this->is_active = false;
        $this->shadow_started_at = now();
        $this->save();
    }

    /**
     * Promote model to active
     */
    public function promoteToActive(): void
    {
        // Deactivate all other active models
        static::where('is_active', true)
            ->where('id', '!=', $this->id)
            ->update([
                'is_active' => false,
                'is_rollback_candidate' => true,
            ]);

        $this->is_shadow = false;
        $this->is_active = true;
        $this->promoted_at = now();
        $this->save();
    }

    /**
     * Rollback to previous model
     */
    public static function rollbackToPrevious(): ?self
    {
        $previousModel = static::where('is_rollback_candidate', true)
            ->where('is_shadow', false)
            ->latest('promoted_at')
            ->first();

        if ($previousModel === null) {
            return null;
        }

        // Deactivate current active model
        $currentActive = static::getActive();
        if ($currentActive !== null) {
            $currentActive->is_active = false;
            $currentActive->save();
        }

        // Promote previous model
        $previousModel->is_active = true;
        $previousModel->is_rollback_candidate = false;
        $previousModel->rollback_reason = 'Manual rollback triggered';
        $previousModel->rolled_back_from_id = $currentActive?->id;
        $previousModel->save();

        return $previousModel;
    }
}
