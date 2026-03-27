<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Http\Requests\CreateBeautyServiceRequest;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Services\BeautyService as BeautyServiceLogic;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Beauty Service Controller (Layer 4)
 */
final class BeautyServiceController extends Controller
{
    public function __construct(
        private readonly BeautyServiceLogic $serviceLogic
    ) {}

    public function index(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $services = BeautyService::query()
                ->where('is_active', true)
                ->with(['salon', 'master'])
                ->paginate(20);
            return response()->json(['success' => true, 'data' => $services, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('API Error: List Services Failed', ['c' => $correlationId, 'e' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error', 'correlation_id' => $correlationId], 500);
        }
    }

    public function store(CreateBeautyServiceRequest $request): JsonResponse 
    {
        $correlationId = (string) Str::uuid();
        try {
            $service = $this->serviceLogic->createService($request->validated(), $correlationId);
            return response()->json(['success' => true, 'data' => $service, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('API Error: Create Service Failed', ['c' => $correlationId, 'e' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => $correlationId], 400);
        }
    }

    public function show(BeautyService $service): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $service->load(['salon', 'master']), 'correlation_id' => (string) Str::uuid()]);
    }
}
