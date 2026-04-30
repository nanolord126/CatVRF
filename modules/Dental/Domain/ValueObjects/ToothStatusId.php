<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\ValueObjects;

use Illuminate\Support\Str;

final readonly class ToothStatusId
{
    private function __construct(public string $value)
    {
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
