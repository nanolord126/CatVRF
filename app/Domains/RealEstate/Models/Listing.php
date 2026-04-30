<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Listing extends Model
{
    use TenantScoped;

    protected $table = 'real_estate_listings';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'property_id',
        'correlation_id',
        'deal_type',
        'price',
        'deposit',
        'commission_percent',
        'is_b2b',
        'rules',
        'status',
        'published_at',
        'tags',
    ];

    protected $casts = [
        'price' => 'integer',
        'deposit' => 'integer',
        'commission_percent' => 'integer',
        'is_b2b' => 'boolean',
        'rules' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function b2bDeals(): HasMany
    {
        return $this->hasMany(B2BDeal::class);
    }

    protected static function booted(): void
    {
        self::creating(function (Listing $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}
