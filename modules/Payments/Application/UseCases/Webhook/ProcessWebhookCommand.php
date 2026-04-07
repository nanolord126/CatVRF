<?php

declare(strict_types=1);

namespace Modules\Payments\Application\UseCases\Webhook;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Class ProcessWebhookCommand
 * 
 * Provides a strongly-typed input command object for processing provider webhooks.
 * Ensures the payload strictly enforces required values before the Use Case processes them.
 */
final readonly class ProcessWebhookCommand implements JsonSerializable
{
    private string $providerCode;
    private array $payload;
    private string $signature;
    private string $correlationId;

    /**
     * ProcessWebhookCommand constructor.
     * 
     * @param string $providerCode Identity of the payment gateway (e.g., 'tinkoff', 'sber').
     * @param array $payload The raw array layout of the JSON sent by the gateway.
     * @param string $signature Cryptographic signature supplied by the webhook headers.
     * @param string $correlationId Tracing ID for telemetry.
     * 
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $providerCode,
        array $payload,
        string $signature,
        string $correlationId
    ) {
        if (trim($providerCode) === '') {
            throw new InvalidArgumentException('Provider code is required for evaluating the correct signature logic.');
        }

        if (empty($payload)) {
            throw new InvalidArgumentException('Webhook payload cannot be legitimately empty.');
        }

        if (trim($correlationId) === '') {
            throw new InvalidArgumentException('A correlation ID must be provided to trace webhook execution.');
        }

        $this->providerCode = $providerCode;
        $this->payload = $payload;
        $this->signature = $signature;
        $this->correlationId = $correlationId;
    }

    /**
     * Returns the exact provider identifier marking this webhook pattern.
     * 
     * @return string
     */
    public function getProviderCode(): string
    {
        return $this->providerCode;
    }

    /**
     * Exposes the nested dictionary payload structure provided by the caller.
     * 
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Gets the generated hash or signature block to prove payload authenticity.
     * 
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Provides the unique process trace tag.
     * 
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Safely transcribes the command back to a dictionary structure.
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'providerCode'  => $this->providerCode,
            'payloadKeys'   => array_keys($this->payload), // Note: Payload values omitted for security
            'signatureLen'  => strlen($this->signature),
            'correlationId' => $this->correlationId,
        ];
    }

    /**
     * Implementation of native JsonSerializable.
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
