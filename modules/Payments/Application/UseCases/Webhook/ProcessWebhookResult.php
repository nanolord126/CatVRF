<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Webhook;

use JsonSerializable;

/**
 * Class ProcessWebhookResult
 * 
 * Defines the structured outcome payload resulting from the processing of a webhook.
 * Ensures that explicit context tracking is returned rather than primitive booleans.
 */
final readonly class ProcessWebhookResult implements JsonSerializable
{
    private string $paymentId;
    private string $finalStatus;
    private bool $processedSilently;
    private string $correlationId;

    /**
     * ProcessWebhookResult constructor.
     * 
     * @param string $paymentId Internal identifier of the targeted payment.
     * @param string $finalStatus Final determined domain status transitioned by this webhook.
     * @param bool $processedSilently Flag depicting if the webhook logic was already applied prior.
     * @param string $correlationId Unified tracking ID linking the process sequence.
     */
    public function __construct(
        string $paymentId,
        string $finalStatus,
        bool $processedSilently,
        string $correlationId
    ) {
        $this->paymentId = $paymentId;
        $this->finalStatus = $finalStatus;
        $this->processedSilently = $processedSilently;
        $this->correlationId = $correlationId;
    }

    /**
     * Obtains the affected internal system Payment ID matching the webhook context.
     * 
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /**
     * Defines the literal domain state the payment finished resolving as.
     * 
     * @return string
     */
    public function getFinalStatus(): string
    {
        return $this->finalStatus;
    }

    /**
     * Denotes if this exact webhook was received previously and successfully discarded to prevent side-effects.
     * 
     * @return bool
     */
    public function isProcessedSilently(): bool
    {
        return $this->processedSilently;
    }

    /**
     * Retrieves the tracking ID associated with the fulfillment chain.
     * 
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Normalizes complex entity properties into standard array layouts for output contexts.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'payment_id'         => $this->paymentId,
            'final_status'       => $this->finalStatus,
            'processed_silently' => $this->processedSilently,
            'correlation_id'     => $this->correlationId,
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
