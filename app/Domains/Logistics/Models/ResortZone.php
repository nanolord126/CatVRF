<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;

/**
 * Resort Zone Model
 *
 * Represents resort/spit/beach zones for adaptive logistics optimization.
 * Stored in MySQL for Filament dashboard management.
 * Synced to ClickHouse for real-time classification queries.
 */
final class ResortZone extends Model
{
    protected $table = 'resort_zones';

    protected $fillable = [
        'tenant_id',
        'zone_name',
        'zone_type',
        'polygon_geojson',
        'linear_density_score',
        'coastline_distance_km',
        'is_resort_spit',
        'max_batch_distance_km',
        'deadhead_ratio_threshold',
        'is_seasonal',
        'season_start_month',
        'season_end_month',
        'peak_hours',
        'preferred_types',
        'pedestrian_max_radius_km',
        'pedestrian_heat_limit_celsius',
        'avg_orders_per_hour',
        'avg_orders_per_km2',
    ];

    protected $casts = [
        'is_resort_spit' => 'boolean',
        'is_seasonal' => 'boolean',
        'max_batch_distance_km' => 'float',
        'deadhead_ratio_threshold' => 'float',
        'linear_density_score' => 'float',
        'coastline_distance_km' => 'float',
        'pedestrian_max_radius_km' => 'float',
        'pedestrian_heat_limit_celsius' => 'float',
        'avg_orders_per_hour' => 'float',
        'avg_orders_per_km2' => 'float',
        'peak_hours' => 'array',
        'preferred_types' => 'array',
        'polygon_geojson' => 'array',
    ];

    /**
     * Get zone type badge color.
     */
    public function getZoneTypeColor(): string
    {
        return match ($this->zone_type) {
            'spit' => 'danger',
            'beach' => 'warning',
            'resort_base' => 'success',
            'coastal_road' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get formatted season range.
     */
    public function getSeasonRange(): string
    {
        if (! $this->is_seasonal) {
            return 'All Year';
        }

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        return $months[$this->season_start_month].' - '.$months[$this->season_end_month];
    }

    /**
     * Check if zone is currently active.
     */
    public function isActive(): bool
    {
        if (! $this->is_seasonal) {
            return true;
        }

        $currentMonth = (int) CarbonImmutable::now()->format('n');

        if ($this->season_start_month > $this->season_end_month) {
            // Year boundary (e.g., November to March)
            return $currentMonth >= $this->season_start_month || $currentMonth <= $this->season_end_month;
        }

        return $currentMonth >= $this->season_start_month && $currentMonth <= $this->season_end_month;
    }
}
