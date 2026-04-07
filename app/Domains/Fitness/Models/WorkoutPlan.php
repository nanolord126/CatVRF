<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Модель плана тренировок.
 * Layer 1 — Models. Канон CatVRF 2026.
 */
final class WorkoutPlan extends Model
{
    protected $table = 'fitness_workout_plans';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'user_id',
        'uuid',
        'correlation_id',
        'name',
        'goal',
        'duration_weeks',
        'sessions_per_week',
        'exercises',
        'notes',
        'is_active',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'exercises' => 'json',
        'tags'      => 'json',
        'metadata'  => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
