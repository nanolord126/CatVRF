<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiTariff extends Model
{
    use HasFactory;

    protected $table = 'taxi_tariffs';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'code',
        'name',
        'description',
        'vehicle_class',
        'icon',
        'color',
        'is_active',
        'is_available_now',
        'base_price',
        'price_per_km',
        'price_per_minute',
        'minimum_price',
        'waiting_price_per_minute',
        'current_surge_multiplier',
        'max_surge_multiplier',
        'fixed_price_available',
        'preorder_available',
        'split_payment_available',
        'corporate_payment_available',
        'voice_order_available',
        'min_vehicle_year',
        'min_vehicle_rating',
        'required_features',
        'passenger_capacity',
        'luggage_capacity',
        'average_wait_time_minutes',
        'max_wait_time_minutes',
        'available_drivers_count',
        'b2b_enabled',
        'b2b_discount_percentage',
        'b2b_monthly_limit',
        'current_promo_code',
        'current_promo_discount',
        'current_promo_valid_until',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'is_active' => 'boolean',
        'is_available_now' => 'boolean',
        'base_price' => 'integer',
        'price_per_km' => 'integer',
        'price_per_minute' => 'integer',
        'minimum_price' => 'integer',
        'waiting_price_per_minute' => 'integer',
        'current_surge_multiplier' => 'float',
        'max_surge_multiplier' => 'float',
        'fixed_price_available' => 'boolean',
        'preorder_available' => 'boolean',
        'split_payment_available' => 'boolean',
        'corporate_payment_available' => 'boolean',
        'voice_order_available' => 'boolean',
        'min_vehicle_year' => 'integer',
        'min_vehicle_rating' => 'float',
        'required_features' => 'array',
        'passenger_capacity' => 'integer',
        'luggage_capacity' => 'integer',
        'average_wait_time_minutes' => 'integer',
        'max_wait_time_minutes' => 'integer',
        'available_drivers_count' => 'integer',
        'b2b_enabled' => 'boolean',
        'b2b_discount_percentage' => 'float',
        'b2b_monthly_limit' => 'integer',
        'current_promo_discount' => 'integer',
        'current_promo_valid_until' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
