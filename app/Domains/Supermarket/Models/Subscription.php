<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Subscription extends Model
{
    use HasFactory, SoftDeletes;

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
        'auto_approved',
    ];

    protected $casts = [
        'next_delivery_at' => 'datetime',
        'pause_until' => 'datetime',
        'total_amount' => 'decimal:2',
        'is_b2b' => 'boolean',
        'auto_approved' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SupermarketOrder::class, 'subscription_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeDueForDelivery($query)
    {
        return $query->where('status', 'active')
            ->where('next_delivery_at', '<=', now());
    }
}
