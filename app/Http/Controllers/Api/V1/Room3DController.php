<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ThreeD\Room3DVisualizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Room3DController
 *
 * Основной класс для работы с платформой CatVRF.
 *
 * @author CatVRF
 * @package App\Http\Controllers\API\V1
 * @version 1.0.0
 */
final class Room3DController extends Controller
{
    public function __construct(private readonly Room3DVisualizerService $service)
    {
    }

    public function visualize(int $roomId, Request $request): JsonResponse
    {
        $roomData = $request->validate([
            'type' => 'string',
            'length' => 'numeric',
            'width' => 'numeric',
            'height' => 'numeric',
            'furniture' => 'array',
        ]);
        $visualization = $this->service->generateRoomVisualization($roomData);
        return response()->json([
            'data' => $visualization,
            'correlation_id' => Str::uuid(),
        ]);
    }
    public function propertyVisualize(int $propertyId, Request $request): JsonResponse
    {
        $propertyData = $request->validate([
            'type' => 'string',
            'rooms' => 'array',
        ]);
        $visualization = $this->service->generatePropertyVisualization($propertyData);
        return response()->json([
            'data' => $visualization,
            'correlation_id' => Str::uuid(),
        ]);
    }
}
