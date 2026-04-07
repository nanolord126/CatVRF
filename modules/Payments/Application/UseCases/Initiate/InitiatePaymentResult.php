<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Initiate;

use JsonSerializable;

/**
 * Class InitiatePaymentResult
 * 
 * Represents the output of the payment initiation use case.
 * Securely encapsulates the resulting state, providing exact fields and ensuring type safety.
 */
final readonly class InitiatePaymentResult implements JsonSerializable
{
    private string $paymentId;
    private string $paymentUrl;
    private string $status;
    private bool $isDuplicate;
    private string $correlationId;

    /**
     * InitiatePaymentResult constructor.
     * 
     * @param string $paymentId Internal unique identifier for the payment.
     * @param string $paymentUrl Gateway URL to which the user should be redirected to complete payment.
     * @param string $status Current domain status of the payment (e.g. 'pending', 'captured').
     * @param bool $isDuplicate Indicates if the payment request matched an existing idempotency key.
     * @param string $correlationId Operational tracing ID connecting this result to its origin request.
     */
    public function __construct(
        string $paymentId,
        string $paymentUrl,
        string $status,
        bool $isDuplicate,
        string $correlationId
    ) {
        $this->paymentId = $paymentId;
        $this->paymentUrl = $paymentUrl;
        $this->status = $status;
        $this->isDuplicate = $isDuplicate;
        $this->correlationId = $correlationId;
    }

    /**
     * Gets the generated internal UUID for the payment.
     * 
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Gets the redirection URL for the client.
     * 
     * @return string
     */
    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    /**
     * Gets the current explicit status of the internal payment entity.
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns true if the initialization hit an idempotency guard and returned a previous instance.
     * 
     * @return bool
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    /**
     * Gets the log correlation tracing unit identifier.
     * 
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Serializes this response to a standard structure usable by presentation layers.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'payment_id'     => $this->paymentId,
            'payment_url'    => $this->paymentUrl,
            'status'         => $this->status,
            'is_duplicate'   => $this->isDuplicate,
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Standard JSON formatting capability.
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
