<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\ValueObjects;

use Illuminate\Support\Str;

/**
 * Warehouse Document ID Value Object
 */
final readonly class WarehouseDocumentId
{
    private function __construct(
        private string $value
    ) {
        if (!Str::isUuid($this->value)) {
            throw new \InvalidArgumentException('Document ID must be a valid UUID');
        }
    }

    public static function generate(): self
    {
        return new self(Str::uuid()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
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
