<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class Timestamp
{
    public function __construct(
        public CarbonImmutable $value,
    ) {
    }

    public static function now(): self
    {
        return new self(CarbonImmutable::now());
    }

    public static function fromString(string $datetime): self
    {
        return new self(CarbonImmutable::parse($datetime));
    }

    public static function fromTimestamp(int $timestamp): self
    {
        return new self(CarbonImmutable::createFromTimestamp($timestamp));
    }

    public function __toString(): string
    {
        return $this->value->toIso8601String();
    }

    public function equals(self $other): bool
    {
        return $this->value->equalTo($other->value);
    }

    public function isBefore(self $other): bool
    {
        return $this->value->lessThan($other->value);
    }

    public function isAfter(self $other): bool
    {
        return $this->value->greaterThan($other->value);
    }
}
