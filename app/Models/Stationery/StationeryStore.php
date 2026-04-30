<?php

declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\BusinessGroup;
use App\Models\Tenant;

final class StationeryStore extends Model
{
    protected $table = 'stationery_stores';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'city',
        'rating',
        'is_active',
        'metadata',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'metadata' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
        'rating' => 'float',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(StationeryProduct::class, 'store_id');
    }

    public function giftSets(): HasMany
    {
        return $this->hasMany(StationeryGiftSet::class, 'store_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if ($this->guard->check() && empty($model->tenant_id)) {
                $model->tenant_id = $this->guard->user()->tenant_id;
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            if ($this->guard->check()) {
                $builder->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }
}
