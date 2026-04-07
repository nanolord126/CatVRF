<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Initiate;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Class InitiatePaymentCommand
 * 
 * Represents the input data transfer object (DTO) required to securely initiate a payment.
 * Implements strict validation on creation to ensure no invalid data reaches the Domain layer.
 */
final readonly class InitiatePaymentCommand implements JsonSerializable
{
    private string $idempotencyKey;
    private int $tenantId;
    private int $userId;
    private int $amountKopeks;
    private string $description;
    private array $metadata;
    private bool $recurrent;
    private string $correlationId;

    /**
     * InitiatePaymentCommand constructor.
     * 
     * @param string $idempotencyKey Unique key to prevent duplicate payments.
     * @param int $tenantId Identifier of the tenant (business) receiving the payment.
     * @param int $userId Identifier of the user making the payment.
     * @param int $amountKopeks Amount in kopeks (MUST be strictly positive).
     * @param string $description Description of the payment for the gateway.
     * @param string $correlationId Tracing ID for logs across the system.
     * @param array $metadata Additional context (like order_id, service_id).
     * @param bool $recurrent Whether the payment should support recurrent charges.
     */
    public function __construct(
        string $idempotencyKey,
        int $tenantId,
        int $userId,
        int $amountKopeks,
        string $description,
        string $correlationId,
        array $metadata = [],
        bool $recurrent = false
    ) {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Idempotency key cannot be empty.');
        }

        if ($tenantId <= 0) {
            throw new InvalidArgumentException('Tenant ID must be a positive integer.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        if ($amountKopeks <= 0) {
            throw new InvalidArgumentException('Amount in kopeks must be strictly greater than zero.');
        }

        if (trim($correlationId) === '') {
            throw new InvalidArgumentException('Correlation ID must not be empty.');
        }

        $this->idempotencyKey = $idempotencyKey;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->amountKopeks = $amountKopeks;
        $this->description = $description;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
        $this->recurrent = $recurrent;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmountKopeks(): int
    {
        return $this->amountKopeks;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isRecurrent(): bool
    {
        return $this->recurrent;
    }

    /**
     * Converts the command to an array for structured logging or debugging.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'idempotencyKey' => $this->idempotencyKey,
            'tenantId'       => $this->tenantId,
            'userId'         => $this->userId,
            'amountKopeks'   => $this->amountKopeks,
            'description'    => $this->description,
            'correlationId'  => $this->correlationId,
            'metadata'       => $this->metadata,
            'recurrent'      => $this->recurrent,
        ];
    }

    /**
     * Implement JsonSerializable for predictable payload casting.
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
