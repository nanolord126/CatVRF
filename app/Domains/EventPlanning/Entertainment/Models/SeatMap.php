<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — SEATMAP MODEL (Entertainment Domain)
 * 1. final class
 * 2. strict_types=1
 * 3. Tenant Scoping (Global Scope)
 * 4. UUID & correlation_id
 * 5. Layout (JSONB)
 */
final class SeatMap extends Model
{
    protected $table = 'entertainment_seat_maps';

    protected $fillable = [
        'uuid',
        'venue_id',
        'tenant_id',
        'name',
        'layout',
        'categories',
        'correlation_id',
    ];

    protected $casts = [
        'layout' => 'json',
        'categories' => 'json',
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

    /* --- Методы Канона --- */

    /**
     * Получить структуру раскладки в виде массива
     */
    public function getLayoutArray(): array
    {
        return is_array($this->layout) ? $this->layout : [];
    }

    /**
     * Получить список категорий мест (VIP, Standard и т.д.)
     */
    public function getCategoriesArray(): array
    {
        return is_array($this->categories) ? $this->categories : [];
    }

    /**
     * Название схемы (для UI)
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * Получение correlation_id
     */
    public function getCorrelationId(): string
    {
        return (string) $this->correlation_id;
    }
}
