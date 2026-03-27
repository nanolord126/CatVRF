<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — PHOTOGRAPHER MODEL
 * 1. specialization + experience_years
 * 2. base_price_hour_kopecks
 */
final class Photographer extends Model
{
    use HasFactory;

    protected $table = 'photography_photographers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'full_name',
        'specialization',
        'experience_years',
        'base_price_hour_kopecks',
        'equipment_json',
        'is_available',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'equipment_json' => 'json',
        'is_available' => 'boolean',
        'base_price_hour_kopecks' => 'integer',
        'experience_years' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'photographer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'photographer_id');
    }
}
