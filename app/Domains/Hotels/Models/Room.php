<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Room — модель номера отеля CatVRF 2026.
 *
 * Хранит данные о номере: тип, вместимость, цены B2C/B2B,
 * доступность и метаданные. Tenant-scoped.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/room
 */
final class Room extends Model
{

    protected $table = 'hotel_rooms';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'hotel_id',
        'room_number',
        'room_type',
        'capacity_adults',
        'capacity_children',
        'base_price_b2c',
        'base_price_b2b',
        'total_stock',
        'min_stay_days',
        'is_available',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'metadata' => 'json',
        'base_price_b2c' => 'integer',
        'base_price_b2b' => 'integer',
        'capacity_adults' => 'integer',
        'capacity_children' => 'integer',
        'total_stock' => 'integer',
        'min_stay_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotel_rooms.tenant_id', tenant()->id);
        });
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'room_id');
    }

    /**
     * Получить цену для конкретного режима (B2C/B2B).
     */
    public function getBasePrice(string $mode = 'b2c'): int
    {
        return $mode === 'b2b'
            ? (int) $this->base_price_b2b
            : (int) $this->base_price_b2c;
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, hotel=%s, type=%s]',
            static::class,
            $this->id ?? 'new',
            $this->hotel_id ?? 'N/A',
            $this->room_type ?? 'unknown',
        );
    }
}
