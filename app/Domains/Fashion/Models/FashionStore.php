<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * КАНЬОН 2026 — МАГАЗИН FASHION (B2B/B2C)
 * 
 * Обязателен tenant_id scoping и uuid.
 * Поддержка ИНН для юрлиц (B2B).
 */
final class FashionStore extends Model
{
    use SoftDeletes;

    protected $table = 'fashion_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'inn',
        'type',
        'schedule_json',
        'rating',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'schedule_json' => 'json',
        'tags' => 'json',
        'is_verified' => 'boolean',
        'rating' => 'float',
    ];

    protected $hidden = [
        'id',
        'correlation_id',
    ];

    /**
     * Глобальный скоуп на tenant_id
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            $tenantId = filament()->getTenant()?->id ?? auth()->user()?->tenant_id;
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = filament()->getTenant()?->id ?? auth()->user()?->tenant_id ?? 0;
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());
            }
        });
    }

    /**
     * Все товары магазина
     */
    public function products(): HasMany
    {
        return $this->hasMany(FashionProduct::class);
    }

    /**
     * Коллекции магазина
     */
    public function collections(): HasMany
    {
        return $this->hasMany(FashionCollection::class);
    }

    /**
     * Оптовые заказы
     */
    public function b2bOrders(): HasMany
    {
        return $this->hasMany(FashionB2BOrder::class);
    }

    public function isB2B(): bool
    {
        return !empty($this->inn);
    }
}
        'correlation_id',
    ];

    protected $casts = [
        'categories' => 'collection',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            if (tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(FashionProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FashionOrder::class);
    }
}
