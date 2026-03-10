<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель города.
 * 
 * @property int $id
 * @property int $district_id ID округа
 * @property string $code Код города (RU-MOW-MSK)
 * @property string $name Название города
 * @property float $latitude Координата широты
 * @property float $longitude Координата долготы
 * @property int $areas_count Количество районов
 * @property int $population Население города
 * @property string|null $timezone Часовой пояс
 * @property bool $is_active Активен ли город
 * @property bool $is_major Крупный ли город (миллионник)
 * @property array|null $metadata
 */
class City extends GeoRoot
{
    protected $fillable = [
        'district_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'areas_count',
        'population',
        'timezone',
        'is_active',
        'is_major',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'areas_count' => 'integer',
        'population' => 'integer',
        'is_active' => 'boolean',
        'is_major' => 'boolean',
        'metadata' => 'array',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /**
     * Получить регион города через округ.
     */
    public function region()
    {
        return $this->district()?->region();
    }

    /**
     * Получить страну города через округ и регион.
     */
    public function country()
    {
        return $this->district()?->region()?->country();
    }

    /**
     * Активные города с районами.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Крупные города (миллионники).
     */
    public function scopeMajor(Builder $query): Builder
    {
        return $query->where('is_major', true)->where('population', '>=', 1000000);
    }

    /**
     * Поиск по коду.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Города в границах определённого квадрата (bounding box).
     */
    public function scopeWithinBox(Builder $query, float $minLat, float $maxLat, float $minLng, float $maxLng): Builder
    {
        return $query->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng]);
    }

    /**
     * Получить полное имя города (регион - город).
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->district?->region?->name} - {$this->name}",
        );
    }

    /**
     * Получить статус города.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => match (true) {
                $this->is_major => 'major',
                $this->population > 500000 => 'large',
                $this->population > 100000 => 'medium',
                default => 'small',
            },
        );
    }

    /**
     * Получить размер города (строка).
     */
    protected function sizeLabel(): Attribute
    {
        $size = $this->status;
        $labels = [
            'major' => 'Город-миллионник',
            'large' => 'Большой город',
            'medium' => 'Средний город',
            'small' => 'Малый город',
        ];

        return Attribute::make(
            get: fn () => $labels[$size] ?? 'Неизвестно',
        );
    }
}
