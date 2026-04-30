<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;

/**
 * Value Objects for Warehouse Domain
 *
 * All value objects combined to avoid stub files
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */

final readonly class WarehouseId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(WarehouseId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class ZoneId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(ZoneId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class BinId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(BinId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class InventoryItemId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(InventoryItemId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class InventoryCountId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(InventoryCountId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class ProductId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(ProductId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class BatchId
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(BatchId $other): bool
    {
        return $this->value === $other->value;
    }
}
