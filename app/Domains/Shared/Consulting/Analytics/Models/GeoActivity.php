<?php

declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class GeoActivity extends Model
{
    public $timestamps = false;


    protected $table = 'geo_activities';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'activity_type',
        'vertical',
        'latitude',
        'longitude',
        'city',
        'region',
        'country',
        'metadata',
        'correlation_id',
        'recorded_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'latitude' => 'float',
        'longitude' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByVertical(Builder $query, string $vertical): Builder
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    public function scopeByActivityType(Builder $query, string $type): Builder
    {
        return $query->where('activity_type', $type);
    }

    /**
     * SECURITY: Анонимизация координат (нормализация до регионов)
     */
    public function getNormalizedLatitude(): float
    {
        return round($this->latitude, 1); // Точность до ~10км
    }

    public function getNormalizedLongitude(): float
    {
        return round($this->longitude, 1);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
