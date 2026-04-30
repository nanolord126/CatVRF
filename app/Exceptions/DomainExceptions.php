<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Domain Exceptions Collection
 * 
 * Consolidated small domain-specific exceptions that don't require
 * complex HTTP rendering or additional context.
 */

final class LogisticsException extends \RuntimeException
{
}

final class EntityNotFoundException extends \RuntimeException
{
    public static function forEntity(string $entity, int|string $id): self
    {
        return new self("Entity [{$entity}] with id [{$id}] not found.");
    }

    public static function forType(string $type): self
    {
        return new self("No [{$type}] entity found.");
    }
}
