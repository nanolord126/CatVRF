<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supermarket\Domain\Enums\SubscriptionStatus;
use Modules\Supermarket\Domain\Enums\SubscriptionFrequency;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $table = 'supermarket_subscriptions';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'status',
        'frequency',
        'delivery_day',
        'next_delivery_at',
        'total_amount',
        'is_b2b',
        'pause_until',
        'cancelled_at',
        'cancelled_reason',
    ];

    protected $casts = [
        'next_delivery_at' => 'datetime',
        'pause_until' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_b2b' => 'boolean',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class, 'subscription_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SupermarketOrder::class, 'subscription_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'seller_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value);
    }

    public function scopePaused($query)
    {
        return $query->where('status', SubscriptionStatus::PAUSED->value);
    }

    public function scopeDueForDelivery($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('next_delivery_at', '<=', now());
    }

    public function scopeByBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    public function scopeBySeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function getStatusEnum(): SubscriptionStatus
    {
        return SubscriptionStatus::from($this->status);
    }

    public function getFrequencyEnum(): SubscriptionFrequency
    {
        return SubscriptionFrequency::from($this->frequency);
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE->value;
    }

    public function isPaused(): bool
    {
        return $this->status === SubscriptionStatus::PAUSED->value;
    }

    public function isPausedAndExpired(): bool
    {
        return $this->isPaused() && $this->pause_until && $this->pause_until->isPast();
    }

    public function canBeProcessed(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->next_delivery_at && $this->next_delivery_at->isPast();
    }

    public function pause(int $days = 7): void
    {
        $this->update([
            'status' => SubscriptionStatus::PAUSED->value,
            'pause_until' => now()->addDays($days),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE->value,
            'pause_until' => null,
            'next_delivery_at' => $this->calculateNextDeliveryDate(),
        ]);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancelled_reason' => $reason,
        ]);
    }

    public function calculateNextDeliveryDate(): Carbon
    {
        $frequency = $this->getFrequencyEnum();
        $deliveryDay = $this->delivery_day;

        return match ($frequency) {
            SubscriptionFrequency::WEEKLY => now()->next((int) $deliveryDay),
            SubscriptionFrequency::BIWEEKLY => now()->addWeek()->next((int) $deliveryDay),
            SubscriptionFrequency::MONTHLY => now()->addMonth()->day((int) $deliveryDay),
        };
    }

    public function getDeliveryFrequencyLabel(): string
    {
        $frequency = $this->getFrequencyEnum();
        $dayName = match ((int) $this->delivery_day) {
            1 => 'понедельник',
            2 => 'вторник',
            3 => 'среда',
            4 => 'четверг',
            5 => 'пятница',
            6 => 'суббота',
            0, 7 => 'воскресенье',
            default => "число {$this->delivery_day}",
        };

        return match ($frequency) {
            SubscriptionFrequency::WEEKLY => "Каждую неделю по {$dayName}",
            SubscriptionFrequency::BIWEEKLY => "Раз в 2 недели по {$dayName}",
            SubscriptionFrequency::MONTHLY => "Каждый месяц, {$dayName}-го числа",
        };
    }
}
