<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * CertificationId — Value Object для ID сертификации
 */
final readonly class CertificationId
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

    public function equals(CertificationId $other): bool
    {
        return $this->value === $other->value;
    }
}
