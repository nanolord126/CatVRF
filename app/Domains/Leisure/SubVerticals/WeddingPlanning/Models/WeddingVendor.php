<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\WeddingPlanning\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class WeddingVendor extends Model
{
    use TenantScoped;

    protected $table = 'wedding_vendors';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'category',
        'base_price',
        'currency',
        'portfolio_links',
        'equipment_list',
        'rating',
        'is_verified',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'portfolio_links' => 'json',
        'equipment_list' => 'json',
        'tags' => 'json',
        'base_price' => 'integer',
        'rating' => 'integer',
        'is_verified' => 'boolean',
    ];

    /**
     * Relation: Bookings
     */
    public function bookings(): HasMany
    {
        return $this->morphMany(WeddingBooking::class, 'bookable');
    }

    /**
     * Relation: Reviews
     */
    public function reviews(): HasMany
    {
        return $this->morphMany(WeddingReview::class, 'reviewable');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $builder->where('wedding_vendors.tenant_id', tenant()->id);
            }
        });

        self::creating(function (Model $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (function_exists('tenant') && is_object(tenant()) && isset(tenant()->id)) {
                $model->tenant_id = $model->tenant_id ?? tenant()->id;
            }
        });
    }
}
