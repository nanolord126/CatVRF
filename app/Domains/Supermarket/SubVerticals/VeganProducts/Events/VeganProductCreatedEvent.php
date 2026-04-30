<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\Events;

/**
 * VeganProductCreatedEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/veganproductcreatedevent
 */
final class VeganProductCreatedEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly VeganProduct $product,
        private readonly int $userId,
        private readonly string $correlationId,
        private readonly array $meta = []
    ) {}
}
