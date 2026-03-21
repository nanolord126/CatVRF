<?php

declare(strict_types=1);

namespace Modules\Hotels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель номера отеля.
 * Согласно КАНОН 2026: tenant scoping, full tracking, soft deletes.
 *
 * @property int $id
 * @property int $hotel_id
 * @property int $tenant_id
 * @property string|null $uuid
 * @property string $number Номер комнаты
 * @property string $name Название номера (Люкс, Стандарт, и т.д.)
 * @property int $price_kopeki Цена в копейках за ночь
 * @property string $status (available, occupied, maintenance, out_of_service)
 * @property bool $is_clean Чистый ли номер
 * @property \Carbon\Carbon|null $last_cleaned_at Время последней уборки
 * @property bool $requires_housekeeping Требуется ли уборка
 * @property bool $needs_laundry Нужна ли стирка
 * @property string $room_type (single, double, suite, etc.)
 * @property float|null $square_meters Площадь в кв.м.
 * @property int $capacity Вместимость людей
 * @property array|null $amenities (WiFi, AC, TV, и т.д.)
 * @property array|null $photos URLs фото номера
 * @property float|null $star_rating Рейтинг номера
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Room extends Model
{
    use SoftDeletes;

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'hotel_id',
        'tenant_id',
        'uuid',
        'number',
        'name',
        'price_kopeki',
        'status',
        'is_clean',
        'last_cleaned_at',
        'requires_housekeeping',
        'needs_laundry',
        'room_type',
        'square_meters',
        'capacity',
        'amenities',
        'photos',
        'star_rating',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'price_kopeki' => 'integer',
        'capacity' => 'integer',
        'is_clean' => 'boolean',
        'requires_housekeeping' => 'boolean',
        'needs_laundry' => 'boolean',
        'square_meters' => 'float',
        'star_rating' => 'float',
        'amenities' => 'json',
        'photos' => 'json',
        'tags' => 'json',
        'last_cleaned_at' => 'datetime',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Статусы номера.
     */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_OUT_OF_SERVICE = 'out_of_service';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить отель, к которому относится номер.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(\Modules\Hotels\Models\Hotel::class);
    }

    /**
     * Получить все бронирования номера.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(\Modules\Hotels\Models\Booking::class, 'room_id');
    }

    /**
     * Получить цену в рублях.
     */
    public function getPriceInRubles(): float
    {
        return $this->price_kopeki / 100;
    }

    /**
     * Установить цену в рублях.
     */
    public function setPriceInRubles(float $rubles): void
    {
        $this->price_kopeki = (int) ($rubles * 100);
    }

    /**
     * Проверить, свободен ли номер.
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Пометить номер как грязный и требующий уборки.
     */
    public function markAsDirty(): void
    {
        $this->update([
            'is_clean' => false,
            'requires_housekeeping' => true,
        ]);
    }

    /**
     * Отметить номер как чистый.
     */
    public function markAsClean(): void
    {
        $this->update([
            'is_clean' => true,
            'requires_housekeeping' => false,
            'last_cleaned_at' => now(),
        ]);
    }
}
