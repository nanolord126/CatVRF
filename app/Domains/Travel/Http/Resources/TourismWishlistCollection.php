<?php declare(strict_types=1);

namespace App\Domains\Travel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Tourism Wishlist Collection Resource
 * 
 * API resource collection for tourism wishlist list responses.
 */
final class TourismWishlistCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => TourismWishlistResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
