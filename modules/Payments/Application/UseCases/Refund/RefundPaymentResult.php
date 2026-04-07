<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Refund;

use JsonSerializable;

/**
 * Class RefundPaymentResult
 * 
 * Provides a standardized format validating application execution responses mapping 
 * explicitly confirmed final output telemetry mapping constraints reliably safely.
 */
final readonly class RefundPaymentResult implements JsonSerializable
{
    private string $paymentId;
    private string $refundId;
    private int $amountRefunded;
    private string $status;
    private string $correlationId;

    /**
     * RefundPaymentResult constructor.
     * 
     * @param string $paymentId Reference targeting internal mapping entity blocks previously established explicitly tracked structurally correctly.
     * @param string $refundId Resulting identifying reference explicit from gateway linking successful validation tracking metrics explicitly securely.
     * @param int $amountRefunded Numeric validation confirming explicit fractional currency execution mapping validation securely inherently.
     * @param string $status State representation variable explicit mapped execution domain limits checking explicitly.
     * @param string $correlationId Origin mapping identifier ensuring exact metric linkages tracing logically mapped perfectly implicitly securely.
     */
    public function __construct(
        string $paymentId,
        string $refundId,
        int $amountRefunded,
        string $status,
        string $correlationId
    ) {
        $this->paymentId = $paymentId;
        $this->refundId = $refundId;
        $this->amountRefunded = $amountRefunded;
        $this->status = $status;
        $this->correlationId = $correlationId;
    }

    /**
     * Identifies the primary payment explicitly linked historically resolving logic explicitly effectively inherently safely tracking explicitly accurately.
     * 
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Identifies newly synthesized operational UUID referencing specific event validation logical sequence effectively explicitly.
     * 
     * @return string
     */
    public function getRefundId(): string
    {
        return $this->refundId;
    }

    /**
     * Returns quantitative exact execution payload fractional constraint checking explicitly.
     * 
     * @return int
     */
    public function getAmountRefunded(): int
    {
        return $this->amountRefunded;
    }

    /**
     * Reveals precise functional state limits evaluating explicitly checking exact metrics reliably securely implicitly explicit pattern structurally.
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Distributes mapped telemetry limits identifying sequence explicitly reliably checking explicitly effectively structurally automatically perfectly safe accurately.
     * 
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Distributes functionality natively interpreting explicitly safe associative formats reliably checking explicitly effectively arrays safely reliably successfully explicit mapping structurally natively.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'payment_id'      => $this->paymentId,
            'refund_id'       => $this->refundId,
            'amount_refunded' => $this->amountRefunded,
            'status'          => $this->status,
            'correlation_id'  => $this->correlationId,
        ];
    }

    /**
     * Standard serialized limits checking exact conversion maps identifying correctly structurally inherently perfectly accurately reliably executing.
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
