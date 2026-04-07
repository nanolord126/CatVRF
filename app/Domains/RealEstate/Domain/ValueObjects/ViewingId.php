<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\ValueObjects;

use InvalidArgumentException;
use Illuminate\Support\Str;

/**
 * Class ViewingId
 *
 * Part of the RealEstate vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\RealEstate\Domain\ValueObjects
 */
final readonly class ViewingId
{
    public function __construct(
        private string $value) {
        if (trim($value) === '') {
            throw new InvalidArgumentException('ViewingId value cannot be empty.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    /**
     * Handle getValue operation.
     *
     * @throws \DomainException
     */
    public function getValue(): string
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
