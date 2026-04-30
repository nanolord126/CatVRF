<?php

declare(strict_types=1);

namespace Modules\Flowers\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Flowers\Domain\Entities\Order as OrderEntity;
use Modules\Flowers\Domain\Enums\OrderStatus;

final class OrderModel extends Model
{
    use SoftDeletes;

    protected $table = 'flowers_orders';

    protected $fillable = [
        'venue_id',
        'client_id',
        'florist_id',
        'tenant_id',
        'order_number',
        'status',
        'delivery_type',
        'delivery_slot_id',
        'delivery_date',
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_instructions',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'total_amount',
        'currency',
        'payment_status',
        'payment_id',
        'card_message',
        'notes',
        'source',
        'is_urgent',
        'is_corporate',
        'loyalty_points_earned',
        'loyalty_points_used',
        'confirmed_at',
        'assembly_started_at',
        'assembled_at',
        'quality_checked_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_urgent' => 'boolean',
        'is_corporate' => 'boolean',
        'loyalty_points_earned' => 'integer',
        'loyalty_points_used' => 'integer',
        'confirmed_at' => 'datetime',
        'assembly_started_at' => 'datetime',
        'assembled_at' => 'datetime',
        'quality_checked_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'status' => OrderStatus::class,
        'metadata' => 'array',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class, 'venue_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function florist(): BelongsTo
    {
        return $this->belongsTo(FloristModel::class, 'florist_id');
    }

    public function deliverySlot(): BelongsTo
    {
        return $this->belongsTo(DeliverySlotModel::class, 'delivery_slot_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderModifierModel::class, 'order_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PhotoModel::class, 'order_id');
    }

    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeInAssembly($query)
    {
        return $query->whereIn('status', [OrderStatus::IN_ASSEMBLY, OrderStatus::ASSEMBLED]);
    }

    public function scopeReadyForDelivery($query)
    {
        return $query->whereIn('status', [OrderStatus::READY_FOR_DELIVERY, OrderStatus::OUT_FOR_DELIVERY]);
    }

    public function scopeByFlorist($query, int $floristId)
    {
        return $query->where('florist_id', $floristId);
    }

    public function scopeByClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeUrgent($query)
    {
        return $query->where('is_urgent', true);
    }

    public function scopeCorporate($query)
    {
        return $query->where('is_corporate', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('delivery_date', '<', now())
            ->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::PICKED_UP, OrderStatus::CANCELLED, OrderStatus::REFUNDED]);
    }

    public function toDomain(): OrderEntity
    {
        return new OrderEntity(
            id: $this->id,
            venueId: $this->venue_id,
            clientId: $this->client_id,
            floristId: $this->florist_id,
            tenantId: $this->tenant_id,
            orderNumber: $this->order_number,
            status: $this->status,
            deliveryType: $this->delivery_type,
            deliverySlotId: $this->delivery_slot_id,
            deliveryDate: $this->delivery_date ? \Carbon\CarbonImmutable::parse($this->delivery_date) : null,
            recipientName: $this->recipient_name,
            recipientPhone: $this->recipient_phone,
            deliveryAddress: $this->delivery_address,
            deliveryInstructions: $this->delivery_instructions,
            subtotal: (float) $this->subtotal,
            deliveryFee: (float) $this->delivery_fee,
            discountAmount: (float) $this->discount_amount,
            totalAmount: (float) $this->total_amount,
            currency: $this->currency,
            paymentStatus: $this->payment_status,
            paymentId: $this->payment_id,
            cardMessage: $this->card_message,
            notes: $this->notes,
            source: $this->source,
            isUrgent: $this->is_urgent,
            isCorporate: $this->is_corporate,
            loyaltyPointsEarned: $this->loyalty_points_earned,
            loyaltyPointsUsed: $this->loyalty_points_used,
            confirmedAt: $this->confirmed_at ? \Carbon\CarbonImmutable::parse($this->confirmed_at) : null,
            assemblyStartedAt: $this->assembly_started_at ? \Carbon\CarbonImmutable::parse($this->assembly_started_at) : null,
            assembledAt: $this->assembled_at ? \Carbon\CarbonImmutable::parse($this->assembled_at) : null,
            qualityCheckedAt: $this->quality_checked_at ? \Carbon\CarbonImmutable::parse($this->quality_checked_at) : null,
            deliveredAt: $this->delivered_at ? \Carbon\CarbonImmutable::parse($this->delivered_at) : null,
            cancelledAt: $this->cancelled_at ? \Carbon\CarbonImmutable::parse($this->cancelled_at) : null,
            cancellationReason: $this->cancellation_reason,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? \Carbon\CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(OrderEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'venue_id' => $entity->venueId,
            'client_id' => $entity->clientId,
            'florist_id' => $entity->floristId,
            'tenant_id' => $entity->tenantId,
            'order_number' => $entity->orderNumber,
            'status' => $entity->status,
            'delivery_type' => $entity->deliveryType,
            'delivery_slot_id' => $entity->deliverySlotId,
            'delivery_date' => $entity->deliveryDate,
            'recipient_name' => $entity->recipientName,
            'recipient_phone' => $entity->recipientPhone,
            'delivery_address' => $entity->deliveryAddress,
            'delivery_instructions' => $entity->deliveryInstructions,
            'subtotal' => $entity->subtotal,
            'delivery_fee' => $entity->deliveryFee,
            'discount_amount' => $entity->discountAmount,
            'total_amount' => $entity->totalAmount,
            'currency' => $entity->currency,
            'payment_status' => $entity->paymentStatus,
            'payment_id' => $entity->paymentId,
            'card_message' => $entity->cardMessage,
            'notes' => $entity->notes,
            'source' => $entity->source,
            'is_urgent' => $entity->isUrgent,
            'is_corporate' => $entity->isCorporate,
            'loyalty_points_earned' => $entity->loyaltyPointsEarned,
            'loyalty_points_used' => $entity->loyaltyPointsUsed,
            'confirmed_at' => $entity->confirmedAt,
            'assembly_started_at' => $entity->assemblyStartedAt,
            'assembled_at' => $entity->assembledAt,
            'quality_checked_at' => $entity->qualityCheckedAt,
            'delivered_at' => $entity->deliveredAt,
            'cancelled_at' => $entity->cancelledAt,
            'cancellation_reason' => $entity->cancellationReason,
            'metadata' => $entity->metadata,
        ]);
    }
}
