<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CrmFitnessProfile — CRM-профиль клиента вертикали Фитнес.
 *
 * Антропометрия, цели, планы тренировок, абонементы, прогресс.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmFitnessProfile extends Model
{
    use TenantScoped;

    protected $table = 'crm_fitness_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'height_cm', 'weight_kg', 'target_weight_kg',
        'body_fat_pct', 'fitness_goal', 'fitness_level', 'health_conditions',
        'preferred_activities', 'disliked_activities', 'membership_type',
        'membership_expires_at', 'visits_per_week', 'training_schedule',
        'body_measurements', 'progress_photos', 'supplements_used',
        'preferred_trainer_id', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'health_conditions' => 'json',
        'preferred_activities' => 'json',
        'disliked_activities' => 'json',
        'training_schedule' => 'json',
        'body_measurements' => 'json',
        'progress_photos' => 'json',
        'supplements_used' => 'json',
        'membership_expires_at' => 'date',
        'height_cm' => 'decimal:1',
        'weight_kg' => 'decimal:1',
        'target_weight_kg' => 'decimal:1',
        'body_fat_pct' => 'decimal:1',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmFitnessProfile[id=%d, goal=%s]', $this->id ?? 0, $this->fitness_goal ?? '');
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model): void {
            if (! $model->uuid && $model->isFillable('uuid')) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
