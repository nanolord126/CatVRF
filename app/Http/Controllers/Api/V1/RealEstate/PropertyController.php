<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\RealEstate;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\RealEstate\Services\PropertyService;
use App\Domains\RealEstate\DTOs\SearchPropertyDto;

final class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $dto = SearchPropertyDto::fromRequest($request);
        $properties = $this->propertyService->searchNearby($dto);

        $responsePayload = $properties->map(function ($property) {
            return [
                "id" => $property->id,
                "title" => $property->title,
                "price" => $property->price,
                "type" => $property->type,
                "lat" => $property->lat,
                "lon" => $property->lon,
                "distance" => round((float) $property->distance, 2),
                "photos" => $property->photos,
            ];
        });

        return new JsonResponse([
            "success" => true,
            "data" => $responsePayload,
            "correlation_id" => $dto->correlationId,
        ], 200);
    }
}
