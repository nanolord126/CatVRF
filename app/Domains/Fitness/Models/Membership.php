<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Модель абонемента в фитнес-клуб.
 * Layer 1 — Models. Канон CatVRF 2026.
 */
final class Membership extends Model
{
    protected $table = 'fitness_memberships';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'gym_id',
        'user_id',
        'uuid',
        'correlation_id',
        'type',
        'duration_days',
        'price',
        'started_at',
        'expires_at',
        'is_active',
        'sessions_included',
        'sessions_used',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags'               => 'json',
        'metadata'           => 'json',
        'is_active'          => 'boolean',
        'price'              => 'decimal:2',
        'started_at'         => 'datetime',
        'expires_at'         => 'datetime',
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
}
