<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\BusinessGroup;

final class LuxuryBrand extends Model
{
    use TenantScoped;

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

    public function products(): HasMany
    {
        return $this->hasMany(LuxuryProduct::class, 'brand_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    protected static function booted_disabled(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('luxury_brands.tenant_id', tenant()->id);
            }
        });
    }
}
