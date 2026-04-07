<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GeoZone extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'logistics_geo_zones';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'slug',
            'type', // district, service_area, restricted, high_demand
            'polygon', // Polygon data (GeoJSON or Point array)
            'radius_km',
            'center_point',
            'is_active',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'polygon' => 'array',
            'center_point' => 'array',
            'radius_km' => 'float',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'tags' => 'array',
            'correlation_id' => 'string',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->slug)) {
                    $model->slug = Str::slug($model->name);
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()?->id) {
                    $model->tenant_id = (int) tenant()?->id;
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()?->id) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        /**
         * Отношения
         */
        public function surgeZones(): HasMany
        {
            return $this->hasMany(SurgeZone::class, 'geo_zone_id');
        }

        public function deliveryOrders(): HasMany
        {
            return $this->hasMany(DeliveryOrder::class, 'geo_zone_id');
        }

        /**
         * Бизнес-логика (2026 Production Ready)
         */
        public function activate(): void
        {
            $this->update(['is_active' => true]);
        }

        public function deactivate(): void
        {
            $this->update(['is_active' => false]);
        }

        /**
         * Проверка вхождения точки в зону (простая реализация через радиус)
         * Для полигонов используется GeoService.
         */
        public function containsPoint(float $lat, float $lon): bool
        {
            if ($this->type === 'circle' && !empty($this->center_point)) {
                $distance = $this->calculateDistance(
                    $lat, $lon,
                    (float)($this->center_point['lat'] ?? 0),
                    (float)($this->center_point['lon'] ?? 0)
                );
                return $distance <= $this->radius_km;
            }

            // Для полигонов логика делегируется в GeoService (Слой 3)
            return false;
        }

        private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
        {
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat / 2) * sin($dLat / 2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return $earthRadius * $c;
        }

        public function getTypeLabel(): string
        {
            return match($this->type) {
                'service_area' => 'Зона обслуживания',
                'restricted' => 'Запретная зона',
                'high_demand' => 'Зона высокого спроса',
                default => 'Общая зона',
            };
        }

        public function getStatusColor(): string
        {
            return $this->is_active ? 'success' : 'danger';
        }
}
