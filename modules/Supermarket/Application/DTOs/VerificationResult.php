<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\DTOs;

readonly class VerificationResult
{
    public function __construct(
        public bool $passed,
        public string $method = '',
        public array $meta = [],
    ) {
    }

    public static function passed(string $method = ''): self
    {
        return new self(
            passed: true,
            method: $method,
            meta: [],
        );
    }

    public static function failed(string $method, array $meta = []): self
    {
        return new self(
            passed: false,
            method: $method,
            meta: $meta,
        );
    }

    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'method' => $this->method,
            'meta' => $this->meta,
        ];
    }
}
