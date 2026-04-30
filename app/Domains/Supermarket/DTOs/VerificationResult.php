<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\DTOs;

use JsonSerializable;

final readonly class VerificationResult implements JsonSerializable
{
    public function __construct(
        public bool $passed,
        public string $method = '',
        public array $meta = []
    ) {}

    public static function passed(string $method = 'automatic'): self
    {
        return new self(
            passed: true,
            method: $method,
            meta: []
        );
    }

    public static function failed(string $reason, array $meta = []): self
    {
        return new self(
            passed: false,
            method: '',
            meta: array_merge(['reason' => $reason], $meta)
        );
    }

    public static function requiresVerification(array $methods, int $restrictedCount): self
    {
        return new self(
            passed: false,
            method: 'required',
            meta: [
                'methods' => $methods,
                'restricted_items_count' => $restrictedCount,
            ]
        );
    }

    public function getReason(): ?string
    {
        return $this->meta['reason'] ?? null;
    }

    public function getAvailableMethods(): array
    {
        return $this->meta['methods'] ?? [];
    }

    public function getRestrictedItemsCount(): int
    {
        return $this->meta['restricted_items_count'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'method' => $this->method,
            'meta' => $this->meta,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function withMeta(array $additionalMeta): self
    {
        return new self(
            passed: $this->passed,
            method: $this->method,
            meta: array_merge($this->meta, $additionalMeta)
        );
    }
}
