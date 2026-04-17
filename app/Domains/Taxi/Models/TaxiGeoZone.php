<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class TaxiGeoZone extends Model
{
    use HasFactory;

    protected $table = 'taxi_geo_zones';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'type',
        'polygon',
        'center_latitude',
        'center_longitude',
        'radius_meters',
        'base_price_multiplier',
        'min_price_kopeki',
        'max_price_kopeki',
        'surge_enabled',
        'surge_multiplier_default',
        'is_active',
        'priority',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'polygon' => 'json',
        'center_latitude' => 'float',
        'center_longitude' => 'float',
        'radius_meters' => 'float',
        'base_price_multiplier' => 'float',
        'min_price_kopeki' => 'integer',
        'max_price_kopeki' => 'integer',
        'surge_enabled' => 'boolean',
        'surge_multiplier_default' => 'float',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Типы зон.
     */
    public const TYPE_CITY = 'city';
    public const TYPE_DISTRICT = 'district';
    public const TYPE_AIRPORT = 'airport';
    public const TYPE_STATION = 'station';
    public const TYPE_BUSINESS = 'business';
    public const TYPE_RESIDENTIAL = 'residential';
    public const TYPE_RESTRICTED = 'restricted';

    protected static function booted(): void
    {
        static::creating(function (TaxiGeoZone $zone) {
            $zone->uuid = $zone->uuid ?? (string) Str::uuid();
            $zone->tenant_id = $zone->tenant_id ?? (tenant()->id ?? 1);
            $zone->is_active = $zone->is_active ?? true;
            $zone->priority = $zone->priority ?? 0;
            $zone->correlation_id = $zone->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    // Note: Surge zones are managed separately through SurgeZone model

    /**
     * Проверить, находится ли точка в зоне.
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (!empty($this->polygon) && is_array($this->polygon)) {
            return $this->pointInPolygon($latitude, $longitude, $this->polygon);
        }

        if ($this->radius_meters > 0) {
            $distance = $this->calculateHaversineDistance(
                $this->center_latitude,
                $this->center_longitude,
                $latitude,
                $longitude
            );
            return $distance <= $this->radius_meters;
        }

        return false;
    }

    /**
     * Проверить точку в полигоне (алгоритм луча).
     */
    private function pointInPolygon(float $lat, float $lon, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'] ?? $polygon[$i][0];
            $yi = $polygon[$i]['lon'] ?? $polygon[$i][1];
            $xj = $polygon[$j]['lat'] ?? $polygon[$j][0];
            $yj = $polygon[$j]['lon'] ?? $polygon[$j][1];

            if ((($yi > $lat) != ($yj > $lat)) &&
                ($lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Рассчитать расстояние по формуле Хаверсина.
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Проверить, активна ли зона.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Получить минимальную цену в рублях.
     */
    public function getMinPriceInRubles(): float
    {
        return $this->min_price_kopeki / 100;
    }

    /**
     * Получить максимальную цену в рублях.
     */
    public function getMaxPriceInRubles(): float
    {
        return $this->max_price_kopeki / 100;
    }
}
