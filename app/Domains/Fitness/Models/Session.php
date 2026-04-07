<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Модель тренировочного занятия (запись к тренеру).
 * Layer 1 — Models. Канон CatVRF 2026.
 */
final class Session extends Model
{
    protected $table = 'fitness_sessions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'gym_id',
        'trainer_id',
        'user_id',
        'uuid',
        'correlation_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'type',
        'notes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags'         => 'json',
        'metadata'     => 'json',
        'scheduled_at' => 'datetime',
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

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
