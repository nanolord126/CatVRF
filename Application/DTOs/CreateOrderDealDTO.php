<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs;

/**
 * DTO for creating an order deal
 */
final readonly class CreateOrderDealDTO
{
    public function __construct(
        public ?string $orderNumber,
        public string $orderType,
        public string $orderStatus,
        public bool $isAgeRestricted,
        public bool $ageVerificationRequired,
        public string $ageVerificationStatus,
        public bool $containsHonestyMarks,
        public int $honestyMarksCount,
        public ?int $subscriptionId,
        public ?string $subscriptionType,
        public ?string $subscriptionDeliveryDay,
        public ?string $subscriptionNextDelivery,
        public ?int $returnId,
        public ?string $returnReason,
        public ?string $returnStatus,
        public float $subtotal,
        public float $discountAmount,
        public float $deliveryFee,
        public float $serviceFee,
        public float $taxAmount,
        public float $totalAmount,
        public string $paymentStatus,
        public ?string $paymentMethod,
        public ?string $paymentTransactionId,
        public ?string $deliveryAddress,
        public ?string $deliveryPhone,
        public ?string $deliveryName,
        public ?string $deliveryScheduledAt,
        public ?string $pickupScheduledAt,
        public ?string $specialRequests,
        public array $allergies,
        public array $dietaryRestrictions,
        public ?string $notes,
        public array $metadata,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderNumber: $data['order_number'] ?? null,
            orderType: $data['order_type'] ?? 'one_time',
            orderStatus: $data['order_status'] ?? 'pending',
            isAgeRestricted: $data['is_age_restricted'] ?? false,
            ageVerificationRequired: $data['age_verification_required'] ?? false,
            ageVerificationStatus: $data['age_verification_status'] ?? 'not_required',
            containsHonestyMarks: $data['contains_honesty_marks'] ?? false,
            honestyMarksCount: $data['honesty_marks_count'] ?? 0,
            subscriptionId: $data['subscription_id'] ?? null,
            subscriptionType: $data['subscription_type'] ?? null,
            subscriptionDeliveryDay: $data['subscription_delivery_day'] ?? null,
            subscriptionNextDelivery: $data['subscription_next_delivery'] ?? null,
            returnId: $data['return_id'] ?? null,
            returnReason: $data['return_reason'] ?? null,
            returnStatus: $data['return_status'] ?? null,
            subtotal: $data['subtotal'] ?? 0,
            discountAmount: $data['discount_amount'] ?? 0,
            deliveryFee: $data['delivery_fee'] ?? 0,
            serviceFee: $data['service_fee'] ?? 0,
            taxAmount: $data['tax_amount'] ?? 0,
            totalAmount: $data['total_amount'] ?? 0,
            paymentStatus: $data['payment_status'] ?? 'pending',
            paymentMethod: $data['payment_method'] ?? null,
            paymentTransactionId: $data['payment_transaction_id'] ?? null,
            deliveryAddress: $data['delivery_address'] ?? null,
            deliveryPhone: $data['delivery_phone'] ?? null,
            deliveryName: $data['delivery_name'] ?? null,
            deliveryScheduledAt: $data['delivery_scheduled_at'] ?? null,
            pickupScheduledAt: $data['pickup_scheduled_at'] ?? null,
            specialRequests: $data['special_requests'] ?? null,
            allergies: $data['allergies'] ?? [],
            dietaryRestrictions: $data['dietary_restrictions'] ?? [],
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'order_type' => $this->orderType,
            'order_status' => $this->orderStatus,
            'is_age_restricted' => $this->isAgeRestricted,
            'age_verification_required' => $this->ageVerificationRequired,
            'age_verification_status' => $this->ageVerificationStatus,
            'contains_honesty_marks' => $this->containsHonestyMarks,
            'honesty_marks_count' => $this->honestyMarksCount,
            'subscription_id' => $this->subscriptionId,
            'subscription_type' => $this->subscriptionType,
            'subscription_delivery_day' => $this->subscriptionDeliveryDay,
            'subscription_next_delivery' => $this->subscriptionNextDelivery,
            'return_id' => $this->returnId,
            'return_reason' => $this->returnReason,
            'return_status' => $this->returnStatus,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discountAmount,
            'delivery_fee' => $this->deliveryFee,
            'service_fee' => $this->serviceFee,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'payment_transaction_id' => $this->paymentTransactionId,
            'delivery_address' => $this->deliveryAddress,
            'delivery_phone' => $this->deliveryPhone,
            'delivery_name' => $this->deliveryName,
            'delivery_scheduled_at' => $this->deliveryScheduledAt,
            'pickup_scheduled_at' => $this->pickupScheduledAt,
            'special_requests' => $this->specialRequests,
            'allergies' => $this->allergies,
            'dietary_restrictions' => $this->dietaryRestrictions,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
