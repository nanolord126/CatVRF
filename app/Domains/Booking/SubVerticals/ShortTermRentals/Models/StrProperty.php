<?php

declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class StrProperty extends Model
{
    use TenantScoped;

    protected $table = 'str_properties';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address',
        'city',
        'lat',
        'lon',
        'type',
        'is_active',
        'is_verified',
        'rating',
        'review_count',
        'schedule_json',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'rating' => 'float',
        'schedule_json' => 'json',
        'tags' => 'json',
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
    ];

    public function apartments(): HasMany
    {
        return $this->hasMany(StrApartment::class, 'property_id');
    }

    /**
     * КАНОН 2026: Автоматическое назначение UUID и tenant scoping
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
