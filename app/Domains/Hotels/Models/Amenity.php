<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Amenity — модель удобства/услуги отеля CatVRF 2026.
 *
 * Описывает доступные удобства (Wi-Fi, бассейн, парковка и т.д.),
 * привязанные к конкретным отелям через pivot-таблицу.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/amenity
 */
final class Amenity extends Model
{

    protected $table = 'hotel_amenities';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'icon',
        'description',
        'cost',
        'is_active',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_amenities.tenant_id', tenant()->id);
        });
    }

    /**
     * Отели, к которым привязана данная услуга.
     */
    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domains\Hotels\HotelManagement\Models\Hotel::class,
            'hotel_amenity_map',
            'amenity_id',
            'hotel_id',
        );
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf('%s[id=%s, name=%s]', static::class, $this->id ?? 'new', $this->name ?? '');
    }

    /**
     * Отладочные данные для логирования.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'cost' => $this->cost,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
