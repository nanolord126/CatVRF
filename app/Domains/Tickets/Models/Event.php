<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель эвента.
 * Слой 2: Модели.
 */
final class Event extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'events';

    protected $fillable = [
        'uuid', 'tenant_id', 'venue_id', 'seat_map_id', 
        'title', 'description', 'slug', 'start_at', 
        'end_at', 'status', 'category', 'max_tickets_per_user', 
        'is_b2b', 'tags', 'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_b2b' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'tags' => 'json',
        'max_tickets_per_user' => 'integer',
        'status' => 'string'
    ];

    /**
     * Глобальные правила эвента.
     */
    protected static function booted(): void
    {
        // 1. Фильтрация по текущему тенанту (multi-tenancy)
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        // 2. Авто-генерация UUID при создании
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant('id');
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'status', 'start_at', 'venue_id', 
                'category', 'max_tickets_per_user'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('audit');
    }

    /**
     * Отношения.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function seatMap(): BelongsTo
    {
        return $this->belongsTo(SeatMap::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Скоуп активных эвентов.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
            ->where('start_at', '>', now());
    }

    /**
     * Проверка на наличие доступных билетов.
     */
    public function hasAvailableTickets(): bool
    {
        return $this->ticketTypes()->sum('quantity') > $this->ticketTypes()->sum('sold_count');
    }

    /**
     * Название категории.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'concert' => 'Концерт',
            'theater' => 'Театр',
            'sport' => 'Спорт',
            'conference' => 'Конференция',
            'festival' => 'Фестиваль',
            default => 'Другое',
        };
    }
}
