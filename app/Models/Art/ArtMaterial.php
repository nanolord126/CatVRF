<?php

declare(strict_types=1);

namespace App\Models\Art;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * ArtMaterial Model — Inventory for creators.
 */
final class ArtMaterial extends Model
{
    protected $table = 'art_materials';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'sku',
        'price_cents',
        'stock_level',
        'min_threshold',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'stock_level' => 'integer',
        'min_threshold' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (ArtMaterial $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
        });

        static::addGlobalScope('tenant_id', function ($builder) {
            $builder->where('tenant_id', (tenant()->id ?? 1));
        });
    }
}
