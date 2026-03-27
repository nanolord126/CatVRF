<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — VENUE MODEL (Entertainment Domain)
 * 1. final class
 * 2. strict_types=1
 * 3. Tenant Scoping (Global Scope)
 * 4. UUID & correlation_id
 * 5. Tags (jsonb)
 */
final class Venue extends Model
{
    use SoftDeletes;

    protected $table = 'entertainment_venues';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'type',
        'address',
        'geo_point',
        'schedule',
        'rating',
        'review_count',
        'is_active',
        'is_b2b_enabled',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'geo_point' => 'json',
        'schedule' => 'json',
        'tags' => 'json',
        'is_active' => 'boolean',
        'is_b2b_enabled' => 'boolean',
        'rating' => 'float',
        'review_count' => 'integer',
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
            // В Production используется tenant()->id, здесь заглушка для тестов
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /* --- Отношения (Relations) --- */

    /**
     * События заведения (сеансы, квесты и т.д.)
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'venue_id');
    }

    /**
     * Схемы залов/столов
     */
    public function seatMaps(): HasMany
    {
        return $this->hasMany(SeatMap::class, 'venue_id');
    }

    /**
     * Отзывы о заведении
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'venue_id');
    }

    /* --- Методы Канона --- */

    /**
     * Проверка: активно ли заведение
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->deleted_at === null;
    }

    /**
     * Проверка: разрешено ли B2B бронирование
     */
    public function isB2BEnabled(): bool
    {
        return $this->is_b2b_enabled;
    }

    /**
     * Получить средний рейтинг из поля модели (кешируется ML/Jobs)
     */
    public function getRating(): float
    {
        return $this->rating;
    }
}
