<?php

declare(strict_types=1);

namespace Modules\Geo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GeoZone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'coordinates', 'tenant_id', 'is_active'];

    protected $casts = ['coordinates' => 'array', 'is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (empty($this->coordinates)) {
            return false;
        }

        $point = ['lat' => $latitude, 'lon' => $longitude];
        $polygon = $this->coordinates;

        return $this->pointInPolygon($point, $polygon);
    }

    private function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point['lon'];
        $y = $point['lat'];
        $inside = false;

        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lon'] ?? $polygon[$i][1];
            $yi = $polygon[$i]['lat'] ?? $polygon[$i][0];
            $xj = $polygon[$j]['lon'] ?? $polygon[$j][1];
            $yj = $polygon[$j]['lat'] ?? $polygon[$j][0];

            if (($yi > $y) !== ($yj > $y)) {
                $intersect = (($xj - $xi) * ($y - $yi)) / ($yj - $yi) + $xi;
                if ($x < $intersect) {
                    $inside = !$inside;
                }
            }
        }

        return $inside;
    }
}
