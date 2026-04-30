<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\DTOs;

use JsonSerializable;

final readonly class SignedCommandDTO implements JsonSerializable
{
    public function __construct(
        public int $deviceId,
        public string $command,
        public array $payload,
        public string $signature,
        public string $timestamp,
        public ?string $nonce = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            deviceId: $data['device_id'],
            command: $data['command'],
            payload: $data['payload'] ?? [],
            signature: $data['signature'] ?? '',
            timestamp: $data['timestamp'] ?? now()->toIso8601String(),
            nonce: $data['nonce'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'command' => $this->command,
            'payload' => $this->payload,
            'signature' => $this->signature,
            'timestamp' => $this->timestamp,
            'nonce' => $this->nonce,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the data that should be signed
     */
    public function getSignableData(): string
    {
        $data = [
            'device_id' => $this->deviceId,
            'command' => $this->command,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp,
            'nonce' => $this->nonce,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Verify the signature against the public key
     */
    public function verifySignature(string $publicKeyPem): bool
    {
        $signableData = $this->getSignableData();
        $signature = base64_decode($this->signature);

        if ($signature === false) {
            return false;
        }

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return false;
        }

        $result = openssl_verify($signableData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($publicKey);

        return $result === 1;
    }
}
