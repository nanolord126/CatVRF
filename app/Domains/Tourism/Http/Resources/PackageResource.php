<?php

declare(strict_types=1);

namespace App\Domains\Tourism\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'destination' => $this->destination,
            'duration_days' => $this->duration_days,
            'price' => (float) $this->price,
            'description' => $this->description,
            'included_activities' => $this->included_activities ? json_decode($this->included_activities, true) : null,
            'accommodation' => $this->accommodation,
            'max_participants' => $this->max_participants,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
