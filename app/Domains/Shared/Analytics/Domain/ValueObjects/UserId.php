<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

final readonly class UserId
{
    public function __construct(
        public int $value,
    ) {
        if ($this->value <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
