<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\ValueObjects;

/**
 * PeerReviewId — Value Object для ID peer review
 */
final readonly class PeerReviewId
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

    public function equals(PeerReviewId $other): bool
    {
        return $this->value === $other->value;
    }
}
