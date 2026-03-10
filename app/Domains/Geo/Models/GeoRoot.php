<?php

namespace App\Domains\Geo\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use Illuminate\Database\Eloquent\{Model, Relations\HasMany, Relations\BelongsTo};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;

/**
 * Базовая модель для всех географических сущностей.
 * 
 * Поддерживает:
 * - Иерархию: Страна → Регион → Округ → Город → Район
 * - Многотенантность через tenant_id
 * - Аудит логирование через HasEcosystemAuth
 * - Координаты и метаданные
 */
class GeoRoot extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Получить предка сущности (страну).
     */
    public function getRoot()
    {
        $model = $this;
        
        while (method_exists($model, 'getParent')) {
            if ($model instanceof Country) {
                return $model;
            }
            if ($model instanceof Region && $model->country) {
                return $model->country;
            }
            if ($model instanceof District && $model->region?->country) {
                return $model->region->country;
            }
            if ($model instanceof City && $model->district?->region?->country) {
                return $model->district->region->country;
            }
            if ($model instanceof Area && $model->city?->district?->region?->country) {
                return $model->city->district->region->country;
            }
            break;
        }

        return null;
    }

    /**
     * Получить полный путь иерархии.
     */
    public function getHierarchyPath(): string
    {
        $path = [];

        if ($this instanceof Country) {
            $path[] = $this->name;
        } elseif ($this instanceof Region) {
            $path[] = $this->country?->name;
            $path[] = $this->name;
        } elseif ($this instanceof District) {
            $path[] = $this->region?->country?->name;
            $path[] = $this->region?->name;
            $path[] = $this->name;
        } elseif ($this instanceof City) {
            $path[] = $this->district?->region?->country?->name;
            $path[] = $this->district?->region?->name;
            $path[] = $this->district?->name;
            $path[] = $this->name;
        } elseif ($this instanceof Area) {
            $path[] = $this->city?->district?->region?->country?->name;
            $path[] = $this->city?->district?->region?->name;
            $path[] = $this->city?->district?->name;
            $path[] = $this->city?->name;
            $path[] = $this->name;
        }

        return implode(' → ', array_filter($path));
    }

    /**
     * Получить уровень иерархии (0=Country, 4=Area).
     */
    public function getHierarchyLevel(): int
    {
        return match (get_class($this)) {
            Country::class => 0,
            Region::class => 1,
            District::class => 2,
            City::class => 3,
            Area::class => 4,
            default => -1,
        };
    }

    /**
     * Получить расстояние между двумя точками (в км).
     * Использует формулу Хаверсинуса.
     */
    public function distanceTo(float $lat, float $lng): ?float
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latDelta = deg2rad($lat - $this->latitude);
        $lngDelta = deg2rad($lng - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }

    /**
     * Проверить, находится ли точка внутри границ квадрата.
     */
    public function isWithinBox(float $minLat, float $maxLat, float $minLng, float $maxLng): bool
    {
        return $this->latitude >= $minLat &&
            $this->latitude <= $maxLat &&
            $this->longitude >= $minLng &&
            $this->longitude <= $maxLng;
    }

    /**
     * Получить метаданные по ключу.
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Установить метаданные по ключу.
     */
    public function setMeta(string $key, $value): self
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Получить иерархический путь для вывода.
     */
    protected function hierarchyPath(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getHierarchyPath(),
        );
    }

    /**
     * Получить уровень в иерархии.
     */
    protected function hierarchyLevel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getHierarchyLevel(),
        );
    }
}
