<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Tourism Booking Collection Resource
 * 
 * API resource collection for tourism booking list responses.
 */
final class TourismBookingCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => TourismBookingResource::collection($this->collection),
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
