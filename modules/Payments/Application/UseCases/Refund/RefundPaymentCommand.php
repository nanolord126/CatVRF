<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Refund;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Class RefundPaymentCommand
 * 
 * Provides a structured input framework specifically responsible for collecting 
 * exact parameters when an intentional financial reversal (refund) is requested.
 */
final readonly class RefundPaymentCommand implements JsonSerializable
{
    private string $paymentId;
    private int $amountKopeks;
    private string $reason;
    private int $userId;
    private string $correlationId;

    /**
     * RefundPaymentCommand constructor.
     * 
     * @param string $paymentId Explicit internal system payment UUID indicating the payment.
     * @param int $amountKopeks Amount requested mapping internally directly onto lowest fraction limits.
     * @param string $reason Business rational requirement forcing the financial transaction.
     * @param int $userId Administrative or user identity requesting explicit operation logic.
     * @param string $correlationId Telemetry and contextual propagation identification block.
     * 
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $paymentId,
        int $amountKopeks,
        string $reason,
        int $userId,
        string $correlationId
    ) {
        if (trim($paymentId) === '') {
            throw new InvalidArgumentException('Payment ID identifier is mandatory for refund targeting logic.');
        }

        if ($amountKopeks <= 0) {
            throw new InvalidArgumentException('Amount in kopeks for a standard refund must be explicitly greater than zero.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Refund reason MUST be explicitly stated mapping internal operational standards.');
        }

        if ($userId <= 0) {
            throw new InvalidArgumentException('Requesting User ID MUST map properly as legitimate internal operational integer reference limits.');
        }

        if (trim($correlationId) === '') {
            throw new InvalidArgumentException('Correlation ID must not be strictly absent preventing functional debugging trails mapping patterns.');
        }

        $this->paymentId = $paymentId;
        $this->amountKopeks = $amountKopeks;
        $this->reason = $reason;
        $this->userId = $userId;
        $this->correlationId = $correlationId;
    }

    /**
     * Retrieves the target internal UUID structure explicitly defined previously mapping instances.
     * 
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Extracts exactly the positive integer representing strict mathematical logic fractional constraints mapping variables.
     * 
     * @return int
     */
    public function getAmountKopeks(): int
    {
        return $this->amountKopeks;
    }

    /**
     * Obtains the explicit reason string provided linking logical operator triggers mapping context formats.
     * 
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Collects explicit reference index variables tying logic explicitly checking patterns mapping triggers operators.
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Distributes mapping logging pattern logic standard execution telemetry blocks tracking variables reliably.
     * 
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Facilitates functional conversion exporting logic properties dynamically mapping associative array constructs patterns explicitly mapped.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'paymentId'     => $this->paymentId,
            'amountKopeks'  => $this->amountKopeks,
            'reason'        => $this->reason,
            'userId'        => $this->userId,
            'correlationId' => $this->correlationId,
        ];
    }

    /**
     * Automates translation protocols ensuring serialization capabilities mapping standard definitions implicitly executed globally dynamically.
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
