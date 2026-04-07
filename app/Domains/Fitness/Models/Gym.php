<?php declare(strict_types=1);

namespace App\Domains\Fitness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Модель фитнес-клуба / зала.
 * Layer 1 — Models. Канон CatVRF 2026.
 */
final class Gym extends Model
{
    protected $table = 'fitness_gyms';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'address',
        'lat',
        'lon',
        'phone',
        'description',
        'amenities',
        'working_hours',
        'is_active',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'amenities'    => 'json',
        'working_hours' => 'json',
        'tags'         => 'json',
        'metadata'     => 'json',
        'is_active'    => 'boolean',
        'lat'          => 'decimal:8',
        'lon'          => 'decimal:8',
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
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'gym_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'gym_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'gym_id');
    }
}
