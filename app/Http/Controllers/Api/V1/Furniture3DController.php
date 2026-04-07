<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ThreeD\FurnitureARService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Furniture3DController
 *
 * Основной класс для работы с платформой CatVRF.
 *
 * @author CatVRF
 * @package App\Http\Controllers\API\V1
 * @version 1.0.0
 */
final class Furniture3DController extends Controller
{
    public function __construct(private readonly FurnitureARService $service,
        private readonly ResponseFactory $response,
    )
    {

    }

    public function generate(int $furnitureId, Request $request): JsonResponse
    {
        $furnitureData = $request->validate([
            'product_id' => 'integer',
            'type' => 'string',
            'width' => 'numeric',
            'height' => 'numeric',
            'depth' => 'numeric',
            'colors' => 'array',
        ]);
        $model = $this->service->generateFurniture3DModel($furnitureData);
        return $this->response->json([
            'data' => $model,
            'correlation_id' => Str::uuid(),
        ]);
    }
    public function roomPlacement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_width' => 'numeric',
            'room_height' => 'numeric',
            'room_depth' => 'numeric',
            'furniture_id' => 'integer',
        ]);
        $visualization = $this->service->roomPlacementVisualization(
            [
                'width' => $data['room_width'],
                'height' => $data['room_height'],
                'depth' => $data['room_depth'],
            ],
            []
        );
        return $this->response->json([
            'data' => $visualization,
            'correlation_id' => Str::uuid(),
        ]);
    }
}
