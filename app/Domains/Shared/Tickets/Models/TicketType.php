<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class TicketType extends Model
{
    use TenantScoped;

    protected $table = 'ticket_types';

    protected $fillable = [
        'uuid', 'tenant_id', 'event_id', 'name',
        'description', 'price', 'quantity', 'sold_count',
        'max_per_order', 'is_active', 'settings',
        'tags', 'correlation_id',
    ];

    protected $casts = [
        'price' => 'integer',
        'quantity' => 'integer',
        'sold_count' => 'integer',
        'max_per_order' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'json',
        'tags' => 'json',
    ];

    /**
     * Эвент типа.
     */
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly EventDispatcher $eventDispatcher,) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo($this->eventDispatcher->class);
    }

    /**
     * Проданные билеты.
     */
    public function soldTickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Осталось в продаже.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->sold_count);
    }

    /**
     * Проверка возможности покупки.
     */
    public function canBuy(int $count): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($count > $this->max_per_order) {
            return false;
        }

        return $this->available_quantity >= $count;
    }

    /**
     * Увеличение счетчика продаж.
     */
    public function incrementSold(int $count = 1): void
    {
        $this->increment('sold_count', $count);
    }

    /**
     * Возврат счетчика при отмене.
     */
    public function decrementSold(int $count = 1): void
    {
        $this->decrement('sold_count', $count);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()?->id;
            }
        });
    }
}
