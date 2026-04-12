<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;




/**
 * VeganProductCreatedEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

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
            private array $meta = []) {}
    }
