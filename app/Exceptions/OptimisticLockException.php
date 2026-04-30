<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * OptimisticLockException - thrown when an optimistic lock conflict occurs.
 *
 * This happens when the version of an entity being updated doesn't match
 * the expected version, indicating that another process has modified the entity.
 */
final readonly class OptimisticLockException extends Exception
{
    /**
     * @param  string  $entityType The type of entity (e.g., 'inventory_item')
     * @param  int  $entityId The ID of the entity
     * @param  int  $expectedVersion The expected version
     * @param  int  $actualVersion The actual version in the database
     */
    public function __construct(
        public string $entityType,
        public int $entityId,
        public int $expectedVersion,
        public int $actualVersion,
    ) {
        parent::__construct(
            sprintf(
                'Optimistic lock conflict for %s (ID: %d). Expected version %d, got %d.',
                $entityType,
                $entityId,
                $expectedVersion,
                $actualVersion
            )
        );
    }
}
