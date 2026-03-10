<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель округа (административный округ, район).
 * 
 * @property int $id
 * @property int $region_id ID региона
 * @property string $code Код округа (RU-MOW-ADMIN)
 * @property string $name Название округа
 * @property float $latitude Центральная координата широты
 * @property float $longitude Центральная координата долготы
 * @property int $cities_count Количество городов
 * @property bool $is_active Активен ли округ
 * @property array|null $metadata
 */
class District extends GeoRoot
{
    protected $fillable = [
        'region_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'cities_count',
        'is_active',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'cities_count' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Получить все районы городов в округе.
     */
    public function allAreas()
    {
        return Area::whereHas('city', function (Builder $query) {
            $query->where('district_id', $this->id);
        });
    }

    /**
     * Получить общее количество локаций в округе.
     */
    public function totalLocations(): int
    {
        return $this->cities()->withCount('areas')->get()->sum(function ($city) {
            return $city->areas_count + 1; // +1 за сам город
        }) + 1; // +1 за сам округ
    }

    /**
     * Активные округа с городами.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('cities_count', '>', 0);
    }

    /**
     * Поиск по коду.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Получить полное обозначение (регион - округ).
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->region?->name} - {$this->name}",
        );
    }

    /**
     * Получить число локаций (городов + районов).
     */
    protected function locationsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cities()->withCount('areas')
                ->get()
                ->sum(fn ($city) => $city->areas_count),
        );
    }
}
