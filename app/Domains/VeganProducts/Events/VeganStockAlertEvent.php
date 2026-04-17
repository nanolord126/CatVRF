<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;




/**
     * VeganStockAlertEvent - Triggered when stock falls below threshold.
     */
final class VeganStockAlertEvent
{
        use \Illuminate\Foundation\Events\Dispatchable, \Illuminate\Queue\SerializesModels;

        public function __construct(
            private readonly VeganProduct $product,
            private readonly int $currentStock,
            private readonly string $correlationId) {}
    }

