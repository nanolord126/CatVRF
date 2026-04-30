<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\ValueObjects\MoneyVO;
use App\Domains\Payment\ValueObjects\PaymentStatusVO;

/**
 * Payment Result DTO - immutable result of payment processing.
 */
final readonly class PaymentResultDTO
{
    public function __construct(
        public string $paymentIntentId,
        public string $paymentTransactionId,
        public PaymentStatusVO $status,
        public MoneyVO $amount,
        public ?string $providerPaymentId = null,
        public ?string $paymentUrl = null,
        public ?string $confirmationUrl = null,
        public ?bool $requiresAction = false,
        public ?string $nextAction = null,
        public ?string $clientSecret = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public ?string $correlationId = null,
        public ?array $gatewayResponse = null,
    ) {}

    /**
     * Create success result.
     */
    public static function success(
        string $paymentIntentId,
        string $paymentTransactionId,
        PaymentStatusVO $status,
        MoneyVO $amount,
        ?string $providerPaymentId = null,
        ?string $paymentUrl = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            paymentIntentId: $paymentIntentId,
            paymentTransactionId: $paymentTransactionId,
            status: $status,
            amount: $amount,
            providerPaymentId: $providerPaymentId,
            paymentUrl: $paymentUrl,
            correlationId: $correlationId,
        );
    }

    /**
     * Create requires action result (3DS, etc.).
     */
    public static function requiresAction(
        string $paymentIntentId,
        string $paymentTransactionId,
        string $nextAction,
        ?string $clientSecret = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            paymentIntentId: $paymentIntentId,
            paymentTransactionId: $paymentTransactionId,
            status: PaymentStatusVO::fromString(PaymentStatusVO::PENDING),
            amount: MoneyVO::fromMajorUnits(0),
            requiresAction: true,
            nextAction: $nextAction,
            clientSecret: $clientSecret,
            correlationId: $correlationId,
        );
    }

    /**
     * Create error result.
     */
    public static function error(
        string $paymentIntentId,
        string $paymentTransactionId,
        string $errorMessage,
        ?string $errorCode = null,
        ?string $correlationId = null,
    ): self {
        return new self(
            paymentIntentId: $paymentIntentId,
            paymentTransactionId: $paymentTransactionId,
            status: PaymentStatusVO::fromString(PaymentStatusVO::FAILED),
            amount: MoneyVO::fromMajorUnits(0),
            errorMessage: $errorMessage,
            errorCode: $errorCode,
            correlationId: $correlationId,
        );
    }

    /**
     * Check if result is successful.
     */
    public function isSuccess(): bool
    {
        return $this->status->isSuccessful() && ! $this->requiresAction;
    }

    /**
     * Check if result requires action (3DS, etc.).
     */
    public function requiresUserAction(): bool
    {
        return $this->requiresAction;
    }

    /**
     * Check if result is error.
     */
    public function isError(): bool
    {
        return $this->errorMessage !== null || $this->status->isFailed();
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'payment_intent_id' => $this->paymentIntentId,
            'payment_transaction_id' => $this->paymentTransactionId,
            'status' => $this->status->status,
            'status_label' => $this->status->label(),
            'amount' => $this->amount->toArray(),
            'provider_payment_id' => $this->providerPaymentId,
            'payment_url' => $this->paymentUrl,
            'confirmation_url' => $this->confirmationUrl,
            'requires_action' => $this->requiresAction,
            'next_action' => $this->nextAction,
            'client_secret' => $this->clientSecret,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'correlation_id' => $this->correlationId,
            'gateway_response' => $this->gatewayResponse,
        ];
    }
}
