<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\ValueObjects;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class RoleId
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Staff\Domain\ValueObjects
 */
final readonly class RoleId
{
    private function __construct(private UuidInterface $uuid)
    {

    }

    public static function fromString(string $uuid): self
    {
        return new self(Uuid::fromString($uuid));
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Handle toString operation.
     *
     * @throws \DomainException
     */
    public function toString(): string
    {
        return $this->uuid->toString();
    }

    /**
     * Handle equals operation.
     *
     * @throws \DomainException
     */
    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }
}
