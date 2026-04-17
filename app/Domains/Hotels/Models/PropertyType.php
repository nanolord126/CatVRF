<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * PropertyType — тип размещения в вертикали Hotels CatVRF 2026.
 *
 * Определяет категорию объекта размещения:
 * - hotel (отель)
 * - sanatorium (санаторий)
 * - boarding_house (пансионат)
 * - recreation_center (дом отдыха)
 * - apartment_daily (квартира посуточно)
 * - aparthotel (апарт-отель)
 * - hostel (хостел)
 * - guest_house (гостевой дом)
 * - villa (вилла)
 *
 * @package CatVRF
 * @version 2026.1
 */
final class PropertyType extends Model
{
    protected $table = 'property_types';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'slug',
        'name',
        'name_ru',
        'description',
        'icon',
        'is_active',
        'sort_order',
        'min_stars',
        'max_stars',
        'features',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'min_stars' => 'integer',
        'max_stars' => 'integer',
        'features' => 'json',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            if (empty($model->slug) && !empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('property_types.tenant_id', tenant()->id);
        });
    }

    /**
     * Отели данного типа размещения.
     */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'property_type_id');
    }

    /**
     * Проверить, допустимо ли указанное количество звёзд для типа размещения.
     */
    public function isValidStars(int $stars): bool
    {
        if ($this->min_stars !== null && $stars < $this->min_stars) {
            return false;
        }

        if ($this->max_stars !== null && $stars > $this->max_stars) {
            return false;
        }

        return true;
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, slug=%s, name=%s]',
            static::class,
            $this->id ?? 'new',
            $this->slug ?? '',
            $this->name_ru ?? $this->name ?? '',
        );
    }
}
