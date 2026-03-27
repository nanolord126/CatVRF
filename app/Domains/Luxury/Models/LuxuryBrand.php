<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * LuxuryBrand
 *
 * Layer 1: Model Layer
 * Описывает ювелирные дома, бренды часов, ателье и другие премиальные марки.
 *
 * @property string $uuid
 * @property int $tenant_id
 * @property string $name
 * @property string $tier
 */
final class LuxuryBrand extends Model
{
    use SoftDeletes;

    protected $table = 'luxury_brands';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'origin_country',
        'tier',
        'website_url',
        'terms_json',
        'tags',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'terms_json' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_brands.tenant_id', tenant()->id);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(LuxuryProduct::class, 'brand_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }
}
