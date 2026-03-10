<?php

namespace App\Domains\Food\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * FoodOrder Model - Заказ еды/ресторан
 * 
 * Production ready model для управления заказами в доменеFood.
 * Содержит информацию о заказе, клиенте, ресторане и статусе.
 */
class FoodOrder extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'food_orders';
    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'customer_id',
        'total_amount',
        'status',
        'items',
        'delivery_address',
        'special_notes',
        'correlation_id',
        'metadata',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'restaurant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    // ============================================
    // BUSINESS LOGIC
    // ============================================

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'payment_pending']);
    }

    public function calculateDeliveryFee(): float
    {
        return $this->total_amount * 0.05; // 5% delivery fee
    }
}
