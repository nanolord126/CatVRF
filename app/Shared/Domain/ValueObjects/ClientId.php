<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Class ClientId
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final readonly class ClientId
{
    public function __construct(private readonly int $value)
    {
        if ($this->value < 1) {
            throw new InvalidArgumentException('ClientId must be a positive integer.');
        }
    }

    /**
     * Handle getValue operation.
     *
     * @throws \DomainException
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Handle equals operation.
     *
     * @throws \DomainException
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the string representation of this object.
     */
    public function __toString(): string
    {
        return self::class.'::'.($this->id ?? 'new');
    }
}
