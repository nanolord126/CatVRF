<?php

declare(strict_types=1);

namespace App\Domains\Food\Beverages\Listeners;

use App\Domains\Food\Beverages\Events\BeverageOrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleBeverageOrderFlow implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BeverageOrderCreated $event): void
    {
        Log::channel('audit')->info('Beverage Order Event Triggered Flow', [
            'order_uuid' => $event->order->uuid,
            'correlation_id' => $event->correlationId,
            'status' => $event->order->status,
        ]);

        // Logic for downstream systems (KDS, Printer, Warehouse) would go here
    }
}
