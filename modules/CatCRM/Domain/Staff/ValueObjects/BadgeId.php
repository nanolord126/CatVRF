<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * BadgeId — Value Object для ID бейджа
 */
final readonly class BadgeId
{
    public function __construct(
        public int $value,
    ) {}

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(BadgeId $other): bool
    {
        return $this->value === $other->value;
    }
}
