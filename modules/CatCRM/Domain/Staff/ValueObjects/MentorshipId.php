<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * MentorshipId — Value Object для ID наставничества
 */
final readonly class MentorshipId
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

    public function equals(MentorshipId $other): bool
    {
        return $this->value === $other->value;
    }
}
