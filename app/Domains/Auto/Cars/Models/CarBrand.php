<?php

declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class CarBrand extends Model
{
    protected $table = 'car_brands';

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'tags' => 'json',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class, 'brand_id');
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
