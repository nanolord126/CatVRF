<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use App\Shared\Domain\ValueObject\ValueObject;
use InvalidArgumentException;

final class UuidValueObject extends ValueObject
{
    public function __construct(
        private readonly string $uuid,
    ) {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            throw new InvalidArgumentException("Invalid UUID: {$uuid}");
        }
    }

    public function value(): string
    {
        return $this->uuid;
    }
}
