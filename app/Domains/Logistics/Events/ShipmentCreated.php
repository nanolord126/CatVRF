<?php declare(strict_types=1);

namespace App\Domains\Logistics\Events;

use App\Domains\Logistics\Models\Shipment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ShipmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public string $correlationId,
    ) {}
}
