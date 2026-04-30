<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Guest Model - Гость ресторана
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Tenant isolation
 * - Correlation ID for tracing
 * - Soft deletes
 */
class Guest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'notes',
        'correlation_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'restaurant_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Restaurant relationship
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Orders relationship
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Reservations relationship
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Scope for restaurant
     */
    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Scope for tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
