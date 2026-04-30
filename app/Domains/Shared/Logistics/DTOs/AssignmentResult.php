<?php

declare(strict_types=1);

namespace App\Domains\Logistics\DTOs;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\OrderShipment;

/**
 * Assignment Result DTO.
 */
final readonly class AssignmentResult
{
    public function __construct(
        public readonly Courier $courier,
        public readonly OrderShipment $shipment,
    ) {}

    public function toArray(): array
    {
        return [
            'courier' => $this->courier->toArray(),
            'shipment' => $this->shipment->toArray(),
            'eta_minutes' => $this->shipment->eta_minutes,
            'distance_km' => $this->shipment->distance_km,
        ];
    }
}
