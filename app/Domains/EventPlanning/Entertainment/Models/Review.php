<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — REVIEW MODEL (Entertainment Domain)
 * 1. final class
 * 2. strict_types=1
 * 3. Tenant Scoping (Global Scope)
 * 4. UUID & correlation_id
 */
final class Review extends Model
{
    protected $table = 'entertainment_reviews';

    protected $fillable = [
        'uuid',
        'venue_id',
        'user_id',
        'tenant_id',
        'rating',
        'comment',
        'photos',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'json',
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
     * Автор отзыва (User)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /* --- Методы Канона --- */

    /**
     * Получить оценку (Rating)
     */
    public function getRating(): int
    {
        return $this->rating;
    }

    /**
     * Получить текст отзыва
     */
    public function getComment(): string
    {
        return (string) $this->comment;
    }

    /**
     * Получить список фото как массив
     */
    public function getPhotosArray(): array
    {
        return is_array($this->photos) ? $this->photos : [];
    }

    /**
     * Получение correlation_id
     */
    public function getCorrelationId(): string
    {
        return (string) $this->correlation_id;
    }
}
