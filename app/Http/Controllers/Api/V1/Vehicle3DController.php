<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ThreeD\VehicleVisualizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Vehicle3DController
 *
 * Основной класс для работы с платформой CatVRF.
 *
 * @author CatVRF
 * @package App\Http\Controllers\API\V1
 * @version 1.0.0
 */
/**
 * Class Vehicle3DController
 *
 * API Controller following CatVRF canon:
 * - Constructor injection for all dependencies
 * - Request validation via Form Requests
 * - Response via ResponseFactory DI
 * - correlation_id in all responses
 *
 * @see \App\Http\Controllers\BaseApiController
 * @package App\Http\Controllers\Api\V1
 */
final class Vehicle3DController extends Controller
{
    public function __construct(private readonly VehicleVisualizerService $service,
        private readonly ResponseFactory $response,
    )
    {

    }

    public function visualize(int $vehicleId, Request $request): JsonResponse
    {
        $vehicleData = $request->validate([
            'type' => 'string',
            'brand' => 'string',
            'model' => 'string',
            'color' => 'string',
            'wheels' => 'integer',
        ]);
        $visualization = $this->service->generateVehicleVisualization($vehicleData);
        return $this->response->json([
            'data' => $visualization,
            'correlation_id' => Str::uuid(),
        ]);
    }
    public function getCameraAngles(int $vehicleId): JsonResponse
    {
        $angles = [
            'front' => ['position' => [0, 1.5, 3], 'target' => [0, 1, 0]],
            'side' => ['position' => [3, 1.5, 0], 'target' => [0, 1, 0]],
            'top' => ['position' => [0, 4, 0], 'target' => [0, 1, 0]],
            'interior' => ['position' => [0, 1, 0], 'target' => [0, 1, -1]],
        ];
        return $this->response->json([
            'data' => $angles,
            'correlation_id' => Str::uuid(),
        ]);
    }
}
