<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;




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
