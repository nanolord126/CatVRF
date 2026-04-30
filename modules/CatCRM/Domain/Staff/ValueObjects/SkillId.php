<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * SkillId — Value Object для ID навыка
 */
final readonly class SkillId
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

    public function equals(SkillId $other): bool
    {
        return $this->value === $other->value;
    }
}
