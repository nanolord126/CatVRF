<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Модель тренера / инструктора.
 * Layer 1 — Models. Канон CatVRF 2026.
 */
final class Trainer extends Model
{
    protected $table = 'fitness_trainers';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'gym_id',
        'uuid',
        'correlation_id',
        'full_name',
        'specialization',
        'bio',
        'rating',
        'photo_url',
        'certifications',
        'is_active',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'certifications' => 'json',
        'tags'           => 'json',
        'metadata'       => 'json',
        'is_active'      => 'boolean',
        'rating'         => 'decimal:2',
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

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'trainer_id');
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class, 'trainer_id');
    }
}
