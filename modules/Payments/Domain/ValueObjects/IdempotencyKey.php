<?php

declare(strict_types=1);

namespace Modules\Payments\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: Ключ идемпотентности.
 */
final readonly class IdempotencyKey
{
    public function __construct(
        public string $value,
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('IdempotencyKey cannot be empty');
        }

        if (strlen($value) > 128) {
            throw new InvalidArgumentException('IdempotencyKey exceeds 128 characters');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(\Illuminate\Support\Str::uuid()->toString());
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
