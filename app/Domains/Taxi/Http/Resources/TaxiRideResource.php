<?php

namespace App\Domains\Taxi\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxiRideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'driver' => $this->whenLoaded('driver', fn() => ['id' => $this->driver->id, 'name' => $this->driver->name]),
            'status' => $this->status,
            'pickup' => ['lat' => $this->pickup_latitude, 'lng' => $this->pickup_longitude],
            'dropoff' => ['lat' => $this->dropoff_latitude, 'lng' => $this->dropoff_longitude],
            'price' => $this->price,
            'distance' => $this->distance,
            'created_at' => $this->created_at,
        ];
    }
}
