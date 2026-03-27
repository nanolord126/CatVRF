<?php

declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — PHOTO SESSION MODEL (PACKAGES)
 */
final class PhotoSession extends Model
{
    protected $table = 'photography_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'vertical_type',
        'duration_minutes',
        'price_kopecks',
        'prepayment_kopecks',
        'includes_json',
        'is_active',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'includes_json' => 'json',
        'is_active' => 'boolean',
        'price_kopecks' => 'integer',
        'prepayment_kopecks' => 'integer',
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
}
