<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Fashion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Fashion Order — Заказ в вертикали Мода
 * 
 * Расширяет базовую Deal модель специфичными полями для магазинов одежды и моды.
 */
final class FashionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'order_type',
        'product_category',
        'items',
        'stylist_id',
        'tailor_id',
        'order_date',
        'estimated_ready_date',
        'actual_ready_date',
        'status',
        'try_on_date',
        'alterations_needed',
        'alterations_description',
        'alterations_completed',
        'alterations_cost',
        'measurements',
        'fit_notes',
        'fabric_type',
        'color_preference',
        'style_preference',
        'occasion',
        'budget_range_min',
        'budget_range_max',
        'subtotal',
        'discount_percent',
        'discount_amount',
        'alterations_amount',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'payment_status',
        'payment_method',
        'shipping_address',
        'shipping_method',
        'tracking_number',
        'delivery_date',
        'pickup_date',
        'return_requested',
        'return_reason',
        'return_date',
        'refund_amount',
        'refund_status',
        'photos',
        'design_sketches',
        'customer_feedback',
        'stylist_notes',
        'reminder_sent',
        'follow_up_sent',
        'cancellation_reason',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'estimated_ready_date' => 'datetime',
        'actual_ready_date' => 'datetime',
        'try_on_date' => 'datetime',
        'delivery_date' => 'datetime',
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'alterations_needed' => 'boolean',
        'alterations_completed' => 'boolean',
        'measurements' => 'json',
        'items' => 'json',
        'budget_range_min' => 'decimal:2',
        'budget_range_max' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'alterations_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'photos' => 'json',
        'design_sketches' => 'json',
        'reminder_sent' => 'boolean',
        'follow_up_sent' => 'boolean',
        'return_requested' => 'boolean',
        'metadata' => 'json',
    ];

    protected $table = 'crm_fashion_orders';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByStylist($query, int $stylistId)
    {
        return $query->where('stylist_id', $stylistId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('product_category', $category);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('order_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready')
            ->where('actual_ready_date', '<=', now());
    }

    public function scopePendingAlterations($query)
    {
        return $query->where('alterations_needed', true)
            ->where('alterations_completed', false);
    }

    public function scopeReadyForTryOn($query)
    {
        return $query->where('status', 'ready_for_try_on');
    }

    public function scopeCustomMade($query)
    {
        return $query->where('order_type', 'custom_made');
    }

    public function scopeReadyToWear($query)
    {
        return $query->where('order_type', 'ready_to_wear');
    }

    public function scopeWithReturn($query)
    {
        return $query->where('return_requested', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ========================
    // METHODS
    // ========================

    public function markReady(): bool
    {
        $this->status = 'ready';
        $this->actual_ready_date = now();
        return $this->save();
    }

    public function scheduleTryOn($date): bool
    {
        $this->status = 'ready_for_try_on';
        $this->try_on_date = $date;
        return $this->save();
    }

    public function requestAlterations(string $description): bool
    {
        $this->alterations_needed = true;
        $this->alterations_description = $description;
        $this->status = 'alterations';
        return $this->save();
    }

    public function completeAlterations(float $cost): bool
    {
        $this->alterations_completed = true;
        $this->alterations_cost = $cost;
        $this->status = 'ready';
        return $this->save();
    }

    public function complete(array $data = []): bool
    {
        $this->status = 'completed';

        if (isset($data['delivery_date'])) {
            $this->delivery_date = $data['delivery_date'];
        }
        if (isset($data['pickup_date'])) {
            $this->pickup_date = $data['pickup_date'];
        }
        if (isset($data['tracking_number'])) {
            $this->tracking_number = $data['tracking_number'];
        }
        if (isset($data['photos'])) {
            $this->photos = $data['photos'];
        }

        return $this->save();
    }

    public function requestReturn(string $reason): bool
    {
        $this->return_requested = true;
        $this->return_reason = $reason;
        $this->return_date = now();
        return $this->save();
    }

    public function processRefund(float $amount): bool
    {
        $this->refund_amount = $amount;
        $this->refund_status = 'processed';
        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function calculateTotal(): float
    {
        $subtotal = $this->subtotal + ($this->alterations_amount ?? 0) + ($this->shipping_cost ?? 0);
        $discount = $subtotal * ($this->discount_percent / 100);
        return $subtotal - $discount - $this->discount_amount + $this->tax_amount;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет total_amount
            if ($model->total_amount === 0) {
                $model->total_amount = $model->calculateTotal();
            }
        });
    }
}
