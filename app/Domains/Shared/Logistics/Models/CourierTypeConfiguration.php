<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Courier Type Configuration model for managing type-specific rules.
 *
 * Stores tenant-specific and city-specific rules for each courier type,
 * allowing flexible configuration without code changes.
 */
final class CourierTypeConfiguration extends Model
{
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'courier_type_rules';

    protected $fillable = [
        'tenant_id',
        'type',
        'city',
        'max_radius_km',
        'max_weight_kg',
        'avg_speed_kmh',
        'max_delivery_time_min',
        'parking_time_min',
        'battery_threshold',
        'requires_battery',
        'allowed_zones',
        'restricted_zones',
        'cost_multiplier',
        'weather_penalties',
        'operating_hours',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'max_radius_km' => 'float',
        'max_weight_kg' => 'float',
        'avg_speed_kmh' => 'float',
        'max_delivery_time_min' => 'integer',
        'parking_time_min' => 'integer',
        'battery_threshold' => 'integer',
        'requires_battery' => 'boolean',
        'allowed_zones' => 'json',
        'restricted_zones' => 'json',
        'cost_multiplier' => 'float',
        'weather_penalties' => 'json',
        'operating_hours' => 'json',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific courier type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific city (or default if null).
     */
    public function scopeForCity($query, ?string $city)
    {
        return $query->where(function ($q) use ($city) {
            $q->where('city', $city)->orWhereNull('city');
        })->orderBy('city', 'desc'); // Prefer city-specific over default
    }

    /**
     * Get weather penalty for condition.
     */
    public function getWeatherPenalty(string $condition): float
    {
        if ($this->weather_penalties === null) {
            return 0.0;
        }

        return $this->weather_penalties[$condition] ?? 0.0;
    }

    /**
     * Check if current time is within operating hours.
     */
    public function isWithinOperatingHours(): bool
    {
        if ($this->operating_hours === null) {
            return true; // 24/7 if not set
        }

        $now = CarbonImmutable::now();
        $start = Carbon::parse($this->operating_hours['start'] ?? '00:00');
        $end = Carbon::parse($this->operating_hours['end'] ?? '23:59');

        return $now->between($start, $end);
    }

    /**
     * Get effective max radius (considering weather penalties).
     */
    public function getEffectiveMaxRadiusKm(float $weatherPenalty = 0.0): float
    {
        return max(0.5, $this->max_radius_km * (1.0 - $weatherPenalty));
    }

    /**
     * Get effective max delivery time (considering weather penalties).
     */
    public function getEffectiveMaxDeliveryTimeMin(float $weatherPenalty = 0.0): int
    {
        return (int) max(15, $this->max_delivery_time_min * (1.0 + $weatherPenalty));
    }
}
