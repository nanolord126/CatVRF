<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentMethodVO;
use App\Domains\Payment\ValueObjects\PaymentStatusVO;

/**
 * Payment Intent DTO - immutable data transfer object for payment intent creation.
 *
 * Represents intent to charge a customer before actual payment processing.
 */
final readonly class PaymentIntentDTO
{
    public function __construct(
        public string $uuid,
        public MoneyVO $amount,
        public PaymentMethodVO $paymentMethod,
        public string $currency,
        public int $tenantId,
        public int $userId,
        public ?int $orderId = null,
        public ?string $description = null,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?string $returnUrl = null,
        public ?string $cancelUrl = null,
        public ?bool $savePaymentMethod = false,
        public ?bool $offSession = false,
        public ?bool $captureMethod = true, // true = automatic, false = manual (hold)
        public ?string $idempotencyKey = null,
        public ?string $correlationId = null,
        public ?array $metadata = null,
        public ?array $items = null,
    ) {}

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'] ?? throw new \InvalidArgumentException('uuid is required'),
            amount: MoneyVO::fromArray($data['amount']),
            paymentMethod: PaymentMethodVO::fromString($data['payment_method']),
            currency: $data['currency'] ?? 'RUB',
            tenantId: $data['tenant_id'] ?? throw new \InvalidArgumentException('tenant_id is required'),
            userId: $data['user_id'] ?? throw new \InvalidArgumentException('user_id is required'),
            orderId: $data['order_id'] ?? null,
            description: $data['description'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
            returnUrl: $data['return_url'] ?? null,
            cancelUrl: $data['cancel_url'] ?? null,
            savePaymentMethod: $data['save_payment_method'] ?? false,
            offSession: $data['off_session'] ?? false,
            captureMethod: $data['capture_method'] ?? true,
            idempotencyKey: $data['idempotency_key'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            items: $data['items'] ?? null,
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'amount' => $this->amount->toArray(),
            'payment_method' => $this->paymentMethod->method,
            'payment_method_provider' => $this->paymentMethod->provider,
            'currency' => $this->currency,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'order_id' => $this->orderId,
            'description' => $this->description,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'return_url' => $this->returnUrl,
            'cancel_url' => $this->cancelUrl,
            'save_payment_method' => $this->savePaymentMethod,
            'off_session' => $this->offSession,
            'capture_method' => $this->captureMethod,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
            'items' => $this->items,
        ];
    }

    /**
     * Check if payment should be captured automatically.
     */
    public function shouldCaptureAutomatically(): bool
    {
        return $this->captureMethod === true;
    }

    /**
     * Check if payment is a hold (manual capture).
     */
    public function isHold(): bool
    {
        return $this->captureMethod === false;
    }
}
