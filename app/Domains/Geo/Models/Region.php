<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель региона (область, край, республика).
 * 
 * @property int $id
 * @property int $country_id ID страны
 * @property string $code Код региона (RU-MOW, RU-SPE)
 * @property string $name Название региона
 * @property string $type Тип региона (oblast, krai, republic)
 * @property float $latitude Центральная координата широты
 * @property float $longitude Центральная координата долготы
 * @property int $districts_count Количество округов
 * @property string $timezone Часовой пояс
 * @property bool $is_active Активен ли регион
 * @property array|null $metadata
 */
class Region extends GeoRoot
{
    protected $fillable = [
        'country_id',
        'code',
        'name',
        'type',
        'latitude',
        'longitude',
        'districts_count',
        'timezone',
        'is_active',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'districts_count' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * Получить все города в регионе.
     */
    public function allCities()
    {
        return City::whereHas('district', function (Builder $query) {
            $query->where('region_id', $this->id);
        });
    }

    /**
     * Получить общее количество локаций в регионе.
     */
    public function totalLocations(): int
    {
        return $this->districts()->withCount(['cities' => function ($q) {
            $q->withCount('areas');
        }])->get()->sum(function ($district) {
            return $district->cities->sum('areas_count') + 1; // +1 за сам район
        }) + 1; // +1 за сам регион
    }

    /**
     * Активные регионы с округами.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('districts_count', '>', 0);
    }

    /**
     * Поиск по коду.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Получить скопированное названием страны и региона.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->country?->name} - {$this->name}",
        );
    }

    /**
     * Получить тип региона с локализацией.
     */
    protected function typeLabel(): Attribute
    {
        $labels = [
            'oblast' => 'Область',
            'krai' => 'Край',
            'republic' => 'Республика',
            'autonomous_okrug' => 'Автономный округ',
            'city' => 'Город федерального значения',
        ];

        return Attribute::make(
            get: fn () => $labels[$this->type] ?? ucfirst($this->type),
        );
    }
}
