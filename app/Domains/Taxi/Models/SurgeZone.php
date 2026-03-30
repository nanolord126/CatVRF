<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeZone extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use LogsActivity;

        protected $table = 'taxi_surge_zones';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'multiplier',
            'boundary_polygon',
            'expires_at',
            'is_active',
            'correlation_id'
        ];

        protected $casts = [
            'boundary_polygon' => 'json',
            'multiplier' => 'float',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'tenant_id' => 'integer'
        ];

        /**
         * Глобальный скоупинг тенанта.
         */
        protected static function booted(): void
        {
            static::creating(function (SurgeZone $zone) {
                $zone->uuid = $zone->uuid ?? (string) Str::uuid();
                $zone->tenant_id = $zone->tenant_id ?? (tenant()->id ?? 1);
                $zone->correlation_id = $zone->correlation_id ?? request()->header('X-Correlation-ID');
            });

            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Настройка логов активности.
         */
        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['multiplier', 'is_active', 'expires_at'])
                ->logOnlyDirty()
                ->dontSubmitEmptyLogs()
                ->setLogName('surge_management');
        }

        /**
         * Проверка на вхождение точки в зону (Ray Casting Algorithm).
         */
        public function containsPoint(float $lat, float $lon): bool
        {
            $polygon = $this->boundary_polygon;
            if (empty($polygon)) return false;

            $nodes = count($polygon);
            $inside = false;
            $j = $nodes - 1;

            for ($i = 0; $i < $nodes; $i++) {
                if (($polygon[$i]['lat'] < $lat && $polygon[$j]['lat'] >= $lat || $polygon[$j]['lat'] < $lat && $polygon[$i]['lat'] >= $lat) &&
                    ($polygon[$i]['lon'] <= $lon || $polygon[$j]['lon'] <= $lon)) {
                    if ($polygon[$i]['lon'] + ($lat - $polygon[$i]['lat']) / ($polygon[$j]['lat'] - $polygon[$i]['lat']) * ($polygon[$j]['lon'] - $polygon[$i]['lon']) < $lon) {
                        $inside = !$inside;
                    }
                }
                $j = $i;
            }

            return $inside;
        }
}
