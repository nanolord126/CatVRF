<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class OrderFulfillment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'order_id',
        'order_type',
        'kitchen_station_id',
        'assigned_chef_id',
        'assigned_waiter_id',
        'status',
        'priority',
        'preparation_started_at',
        'ready_at',
        'served_at',
        'duration_minutes',
        'items',
        'special_instructions',
        'allergies',
        'notes',
        'quality_check',
        'customer_rating',
        'delay_reason',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'preparation_started_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'duration_minutes' => 'integer',
        'priority' => 'integer',
        'items' => 'json',
        'quality_check' => 'boolean',
        'customer_rating' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'restaurant_order_fulfillments';

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Restaurant\Domain\Entities\Restaurant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Restaurant\Domain\Entities\Order::class);
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(\Modules\Restaurant\Domain\Entities\KitchenStation::class);
    }

    public function assignedChef(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_chef_id');
    }

    public function assignedWaiter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_waiter_id');
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeByOrderType($query, string $orderType)
    {
        return $query->where('order_type', $orderType);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeServed($query)
    {
        return $query->where('status', 'served');
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', 'in_progress')
            ->where('preparation_started_at', '<', now()->subMinutes(30));
    }

    public function startPreparation(): bool
    {
        return $this->update([
            'status' => 'in_progress',
            'preparation_started_at' => now(),
        ]);
    }

    public function markReady(): bool
    {
        return $this->update([
            'status' => 'ready',
            'ready_at' => now(),
            'duration_minutes' => $this->preparation_started_at 
                ? now()->diffInMinutes($this->preparation_started_at) 
                : null,
        ]);
    }

    public function markServed(): bool
    {
        return $this->update([
            'status' => 'served',
            'served_at' => now(),
        ]);
    }

    public function recordDelay(string $reason): bool
    {
        return $this->update([
            'delay_reason' => $reason,
        ]);
    }

    public function setRating(int $rating): bool
    {
        return $this->update([
            'customer_rating' => $rating,
        ]);
    }

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn ($m) => $m->uuid ??= \Str::uuid());
    }
}
