<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель районов города.
 * 
 * @property int $id
 * @property int $city_id ID города
 * @property string $code Код района (RU-MOW-MSK-ADM)
 * @property string $name Название района
 * @property float $latitude Координата широты
 * @property float $longitude Координата долготы
 * @property int $buildings_count Количество зданий
 * @property int $streets_count Количество улиц
 * @property bool $is_active Активен ли район
 * @property string|null $type Тип района (residential, commercial, mixed)
 * @property array|null $metadata Метаданные (density, avg_income, etc)
 */
class Area extends GeoRoot
{
    protected $table = 'areas';

    protected $fillable = [
        'city_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'buildings_count',
        'streets_count',
        'is_active',
        'type',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'buildings_count' => 'integer',
        'streets_count' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Получить округ района через город.
     */
    public function district()
    {
        return $this->city()?->district();
    }

    /**
     * Получить регион района.
     */
    public function region()
    {
        return $this->city()?->district()?->region();
    }

    /**
     * Получить страну района.
     */
    public function country()
    {
        return $this->city()?->district()?->region()?->country();
    }

    /**
     * Активные районы.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Поиск по коду.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Районы определённого типа.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Районы в границах квадрата.
     */
    public function scopeWithinBox(Builder $query, float $minLat, float $maxLat, float $minLng, float $maxLng): Builder
    {
        return $query->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng]);
    }

    /**
     * Плотность застройки (здания на улицу).
     */
    protected function buildingDensity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->streets_count > 0
                ? round($this->buildings_count / $this->streets_count, 2)
                : 0,
        );
    }

    /**
     * Получить полное имя района (город - район).
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->city?->name} - {$this->name}",
        );
    }

    /**
     * Получить тип района с локализацией.
     */
    protected function typeLabel(): Attribute
    {
        $labels = [
            'residential' => 'Жилой',
            'commercial' => 'Коммерческий',
            'mixed' => 'Смешанный',
            'industrial' => 'Промышленный',
            'historical' => 'Исторический',
        ];

        return Attribute::make(
            get: fn () => $labels[$this->type] ?? ucfirst($this->type ?? 'неизвестно'),
        );
    }
}
