<?php declare(strict_types=1);

namespace App\Domains\Beauty\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class BookingSlotCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => BookingSlotResource::collection($this->collection),
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }
}
