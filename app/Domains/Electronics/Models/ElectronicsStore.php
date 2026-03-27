<?php

declare(strict_types=1);

namespace App\Domains\Electronics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ElectronicsStore - Retail outlets or fulfillment points.
 * Final class, strict types, tenant scoping.
 */
final class ElectronicsStore extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'electronics_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'address',
        'working_hours',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'working_hours' => 'json',
        'tags' => 'json',
    ];

    /**
     * Global Scope: Tenant Isolation.
     */
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /* --- Relations --- */

    public function products(): HasMany
    {
        return $this->hasMany(ElectronicsProduct::class, 'store_id');
    }

    /* --- Helpers --- */

    public function getIsOpenAttribute(): bool
    {
        // Simple mock for logic, real imp would check current time vs working_hours JSON
        return true;
    }
}

/**
 * ElectronicsCategory - Product classification.
 */
final class ElectronicsCategory extends Model
{
    use HasFactory;

    protected $table = 'electronics_categories';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'icon',
        'correlation_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(ElectronicsProduct::class, 'category_id');
    }
}

/**
 * ElectronicsGadget - Technical specs extension for smart products.
 */
final class ElectronicsGadget extends Model
{
    protected $table = 'electronics_gadgets';

    protected $fillable = [
        'product_id',
        'os_version',
        'cpu_model',
        'ram_gb',
        'storage_gb',
        'screen_size_inch',
        'battery_mah',
        'is_5g_ready',
    ];

    protected $casts = [
        'ram_gb' => 'integer',
        'storage_gb' => 'integer',
        'screen_size_inch' => 'float',
        'battery_mah' => 'integer',
        'is_5g_ready' => 'boolean',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ElectronicsProduct::class, 'product_id');
    }
}

/**
 * ElectronicsWarranty - Service assurance data.
 */
final class ElectronicsWarranty extends Model
{
    protected $table = 'electronics_warranties';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'product_id',
        'order_id',
        'user_id',
        'serial_number',
        'starts_at',
        'expires_at',
        'status',
        'terms',
        'correlation_id',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?: (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ElectronicsProduct::class, 'product_id');
    }

    public function scopeIsActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('expires_at', '>=', now());
    }
}

/**
 * ElectronicsReview - User feedback.
 */
final class ElectronicsReview extends Model
{
    protected $table = 'electronics_reviews';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'rating',
        'comment',
        'images',
        'is_verified_purchase',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'json',
        'is_verified_purchase' => 'boolean',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ElectronicsProduct::class, 'product_id');
    }
}
