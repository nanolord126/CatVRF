<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ThreeD\Product3DService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Routing\ResponseFactory;

final class Product3DController extends Controller
{
    public function __construct(private readonly Product3DService $service,
        private readonly ResponseFactory $response,
    ) {}
    public function index(int $verticalId): JsonResponse
    {
        // Get all 3D models for vertical
        return $this->response->json([
            'data' => [],
            'correlation_id' => Str::uuid(),
        ]);
    }
    public function show(int $productId): JsonResponse
    {
        $model = $this->service->getProduct3DModel($productId);
        if (!$model) {
            return $this->response->json(['error' => 'Model not found'], 404);
        }
        return $this->response->json([
            'data' => $model,
            'correlation_id' => Str::uuid(),
        ]);
    }
    public function upload(Request $request, int $productId, string $vertical): JsonResponse
    {
        $validated = $request->validate([
            '3d_model' => 'required|file|mimes:glb,gltf,obj',
        ]);
        if (!$this->service->validate3DModel($validated['3d_model']->getPathname())) {
            return $this->response->json(['error' => 'Invalid 3D format'], 422);
        }
        $result = $this->service->uploadProduct3DModel(
            $validated['3d_model']->getPathname(),
            (string) $productId,
            $vertical
        );
        return $this->response->json([
            'data' => $result,
            'correlation_id' => Str::uuid(),
        ], 201);
    }
    public function getThumbnail(int $productId): JsonResponse
    {
        $model = $this->service->getProduct3DModel($productId);
        if (!$model) {
            return $this->response->json(['error' => 'Model not found'], 404);
        }
        $thumbnail = $this->service->generate3DThumbbnail($model['path']);
        return $this->response->json([
            'thumbnail_url' => $thumbnail,
            'correlation_id' => Str::uuid(),
        ]);
    }
}
