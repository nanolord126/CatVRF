<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — EVENT MODEL (Entertainment Domain)
 * 1. final class
 * 2. strict_types=1
 * 3. Tenant Scoping (Global Scope)
 * 4. UUID & correlation_id
 * 5. capacity checks
 */
final class Event extends Model
{
    protected $table = 'entertainment_events';

    protected $fillable = [
        'uuid',
        'venue_id',
        'tenant_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'base_price_kopecks',
        'total_capacity',
        'available_capacity',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'base_price_kopecks' => 'integer',
        'total_capacity' => 'integer',
        'available_capacity' => 'integer',
        'tags' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    /**
     * КАНОН: Инициализация модели, авто-генерация UUID и Global Scope
     */
    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /* --- Отношения (Relations) --- */

    /**
     * Заведение (Venue)
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    /**
     * Бронирования этого события
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'event_id');
    }

    /**
     * Билеты этого события
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }

    /* --- Методы Канона --- */

    /**
     * Проверка: активно ли событие
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->starts_at->isFuture();
    }

    /**
     * Проверка: есть ли свободные места
     */
    public function hasCapacity(int $count = 1): bool
    {
        return $this->available_capacity >= $count;
    }

    /**
     * Уменьшить количество свободных мест (атомарно в сервисе)
     */
    public function decrementCapacity(int $count = 1): void
    {
        if (!$this->hasCapacity($count)) {
            // Ошибка бросается в сервисе (InsufficientCapacityException)
            return;
        }
        $this->decrement('available_capacity', $count);
    }

    /**
     * Увеличить количество свободных мест
     */
    public function incrementCapacity(int $count = 1): void
    {
        if ($this->available_capacity + $count > $this->total_capacity) {
            $this->available_capacity = $this->total_capacity;
        } else {
            $this->increment('available_capacity', $count);
        }
        $this->save();
    }

    /**
     * Получить цену в рублях
     */
    public function getBasePriceAmount(): float
    {
        return $this->base_price_kopecks / 100;
    }
}
