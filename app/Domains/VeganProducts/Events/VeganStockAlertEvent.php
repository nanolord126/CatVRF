<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;
use Dispatchable, SerializesModels;

/**
     * VeganStockAlertEvent - Triggered when stock falls below threshold.
     */
final class VeganStockAlertEvent
{
        use Dispatchable, SerializesModels;

        public function __construct(
            private readonly VeganProduct $product,
            private readonly int $currentStock,
            private readonly string $correlationId) {}
    }
