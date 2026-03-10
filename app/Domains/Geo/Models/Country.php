<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель страны в географической иерархии.
 * 
 * @property int $id
 * @property string $code ISO 3166-1 alpha-2 код (RU, US, etc)
 * @property string $name Название страны
 * @property string $name_en Название на английском
 * @property float $latitude Центральная координата широты
 * @property float $longitude Центральная координата долготы
 * @property int $regions_count Количество регионов
 * @property bool $is_active Активна ли страна
 * @property array|null $metadata Дополнительные метаданные
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Country extends GeoRoot
{
    protected $fillable = [
        'code',
        'name',
        'name_en',
        'latitude',
        'longitude',
        'regions_count',
        'is_active',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'regions_count' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Получить все города через регионы и округа.
     */
    public function allCities()
    {
        return City::whereHas('district.region', function (Builder $query) {
            $query->where('country_id', $this->id);
        });
    }

    /**
     * Получить все локации (города, районы) в стране.
     */
    public function totalLocations(): int
    {
        return $this->regions()->withCount(['districts' => function ($q) {
            $q->withCount(['cities' => function ($q2) {
                $q2->withCount('areas');
            }]);
        }])->get()->sum(function ($region) {
            return $region->districts->sum(function ($district) {
                return $district->cities->sum('areas_count') + 1; // +1 за сам район
            }) + 1; // +1 за сам регион
        }) + 1; // +1 за саму страну
    }

    /**
     * Проверить, активна ли страна и имеет регионы.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('regions_count', '>', 0);
    }

    /**
     * Получить по коду ISO.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Получить координаты страны.
     */
    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        );
    }

    /**
     * Получить полное название (код + имя).
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->code} - {$this->name}",
        );
    }
}
