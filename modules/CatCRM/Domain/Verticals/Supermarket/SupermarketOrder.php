<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Supermarket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Supermarket Order — Заказ в вертикали Супермаркет
 * 
 * Расширяет базовую Deal модель специфичными полями для супермаркетов.
 * Поддерживает разовые заказы, подписки и возвраты.
 */
final class SupermarketOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'supermarket_id',
        'order_number',
        'order_type', // one_time, subscription, return
        'delivery_type', // courier, pickup
        'order_status',
        'is_age_restricted',
        'age_verification_required',
        'age_verification_status', // pending, verified, failed, not_required
        'age_verification_method',
        'age_verified_at',
        'contains_honesty_marks',
        'honesty_marks_count',
        'honesty_marks_validated',
        'subscription_id',
        'subscription_type', // weekly, bi_weekly, monthly
        'subscription_delivery_day',
        'subscription_next_delivery',
        'return_id',
        'return_reason',
        'return_status',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'service_fee',
        'tax_amount',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
        'delivery_address',
        'delivery_phone',
        'delivery_name',
        'delivery_scheduled_at',
        'delivery_actual_at',
        'pickup_scheduled_at',
        'pickup_actual_at',
        'special_requests',
        'allergies',
        'dietary_restrictions',
        'notes',
        'cancellation_reason',
        'metadata',
        'correlation_id',
        'uuid',
        // Courier Information
        'courier_id',
        'courier_name',
        'courier_phone',
        'courier_vehicle_type',
        'delivery_route',
        'courier_location_lat',
        'courier_location_lng',
        'courier_location_updated_at',
        'courier_assigned_at',
        'courier_arrived_at',
        'delivery_eta',
        'delivery_progress',
        'handoff_method',
        'delivery_photo_url',
        'client_signature_url',
        'handoff_at',
        // Pickup Information
        'pickup_zone',
        'locker_number',
        'qr_code',
        'pickup_ready_at',
        'pickup_window_start',
        'pickup_window_end',
        'customer_arrived_at',
        'pickup_operator_id',
        'pickup_started_at',
        'id_verified_at',
        'pickup_photo_url',
        // Processing Information
        'warehouse_id',
        'processing_operator_id',
        'processing_started_at',
        'items_picked_count',
        'items_missing',
        'quality_check_passed',
        'expiry_check_passed',
        'cold_chain_temperature',
        'storage_location',
        'storage_temperature',
        // Nutritional Information
        'total_calories',
        'total_proteins',
        'total_fats',
        'total_carbs',
        'total_fiber',
        'total_sugar',
        'total_sodium',
        // Item Metadata
        'items_count',
        'items_weight',
        'items_volume',
        'contains_perishable',
        'contains_cold_chain',
        // Allergen Warnings
        'allergen_warnings',
        'allergen_details',
        // Ratings and Feedback
        'delivery_rating',
        'pickup_rating',
        'courier_rating',
        'delivery_feedback',
        'pickup_feedback',
        'delivery_issues',
        'pickup_issues',
        // Performance Metrics
        'fulfillment_duration',
        'on_time_delivery',
        'queue_wait_time',
    ];

    protected $casts = [
        'is_age_restricted' => 'boolean',
        'age_verification_required' => 'boolean',
        'age_verified_at' => 'datetime',
        'contains_honesty_marks' => 'boolean',
        'honesty_marks_validated' => 'boolean',
        'subscription_next_delivery' => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'delivery_actual_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'pickup_actual_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'allergies' => 'json',
        'dietary_restrictions' => 'json',
        'metadata' => 'json',
        // Courier casts
        'delivery_route' => 'json',
        'courier_location_updated_at' => 'datetime',
        'courier_assigned_at' => 'datetime',
        'courier_arrived_at' => 'datetime',
        'delivery_eta' => 'datetime',
        'handoff_at' => 'datetime',
        // Pickup casts
        'pickup_ready_at' => 'datetime',
        'pickup_window_start' => 'datetime',
        'pickup_window_end' => 'datetime',
        'customer_arrived_at' => 'datetime',
        'pickup_started_at' => 'datetime',
        'id_verified_at' => 'datetime',
        // Processing casts
        'processing_started_at' => 'datetime',
        'items_missing' => 'json',
        // Nutritional casts
        'total_proteins' => 'decimal:2',
        'total_fats' => 'decimal:2',
        'total_carbs' => 'decimal:2',
        'total_fiber' => 'decimal:2',
        'total_sugar' => 'decimal:2',
        'items_weight' => 'decimal:3',
        'items_volume' => 'decimal:3',
        // Allergen casts
        'allergen_warnings' => 'json',
        'allergen_details' => 'json',
        // Issues casts
        'delivery_issues' => 'json',
        'pickup_issues' => 'json',
    ];

    protected $table = 'crm_supermarket_orders';

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

    public function scopeBySupermarket($query, int $supermarketId)
    {
        return $query->where('supermarket_id', $supermarketId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('order_status', $status);
    }

    public function scopeOneTime($query)
    {
        return $query->where('order_type', 'one_time');
    }

    public function scopeSubscription($query)
    {
        return $query->where('order_type', 'subscription');
    }

    public function scopeReturn($query)
    {
        return $query->where('order_type', 'return');
    }

    public function scopeAgeRestricted($query)
    {
        return $query->where('is_age_restricted', true);
    }

    public function scopeRequiresAgeVerification($query)
    {
        return $query->where('age_verification_required', true);
    }

    public function scopeAgeVerified($query)
    {
        return $query->where('age_verification_status', 'verified');
    }

    public function scopeWithHonestyMarks($query)
    {
        return $query->where('contains_honesty_marks', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeUpcomingDeliveries($query)
    {
        return $query->where('delivery_scheduled_at', '>=', now())
            ->whereIn('order_status', ['confirmed', 'processing']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('order_status', ['confirmed', 'processing', 'delivering']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('order_status', 'cancelled');
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->order_status = 'confirmed';
        return $this->save();
    }

    public function startProcessing(): bool
    {
        $this->order_status = 'processing';
        return $this->save();
    }

    public function markReady(): bool
    {
        $this->order_status = 'ready';
        return $this->save();
    }

    public function startDelivery(): bool
    {
        $this->order_status = 'delivering';
        return $this->save();
    }

    public function markDelivered(): bool
    {
        $this->order_status = 'delivered';
        $this->delivery_actual_at = now();
        return $this->save();
    }

    public function markPickedUp(): bool
    {
        $this->order_status = 'picked_up';
        $this->pickup_actual_at = now();
        return $this->save();
    }

    public function complete(): bool
    {
        $this->order_status = 'completed';
        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->order_status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function verifyAge(string $method): bool
    {
        $this->age_verification_status = 'verified';
        $this->age_verification_method = $method;
        $this->age_verified_at = now();
        return $this->save();
    }

    public function failAgeVerification(): bool
    {
        $this->age_verification_status = 'failed';
        return $this->save();
    }

    public function validateHonestyMarks(): bool
    {
        $this->honesty_marks_validated = true;
        return $this->save();
    }

    public function isOneTime(): bool
    {
        return $this->order_type === 'one_time';
    }

    public function isSubscription(): bool
    {
        return $this->order_type === 'subscription';
    }

    public function isReturn(): bool
    {
        return $this->order_type === 'return';
    }

    public function isAgeRestricted(): bool
    {
        return $this->is_age_restricted;
    }

    public function isAgeVerified(): bool
    {
        return $this->age_verification_status === 'verified';
    }

    public function requiresAgeVerification(): bool
    {
        return $this->age_verification_required;
    }

    public function isDelivery(): bool
    {
        return $this->delivery_scheduled_at !== null;
    }

    public function isPickup(): bool
    {
        return $this->pickup_scheduled_at !== null;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Генерация order_number
            if (!$model->order_number) {
                $prefix = match($model->order_type) {
                    'subscription' => 'SUB',
                    'return' => 'RET',
                    default => 'ORD',
                };
                $model->order_number = $prefix . '-' . date('Ymd') . '-' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            // Автоматический расчёт total_amount
            if ($model->total_amount === 0 && $model->subtotal > 0) {
                $model->total_amount = $model->subtotal 
                    - $model->discount_amount 
                    + $model->delivery_fee 
                    + $model->service_fee 
                    + $model->tax_amount;
            }

            // Автоматическая установка age_verification_required если есть возрастные ограничения
            if ($model->is_age_restricted && !$model->age_verification_status) {
                $model->age_verification_required = true;
                $model->age_verification_status = 'pending';
            }
        });
    }
}
