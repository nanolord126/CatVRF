<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObjects;

final readonly class PaymentId
{
    private function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self(0); // Will be set by database
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
