<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalonResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
        ];
    }
}
