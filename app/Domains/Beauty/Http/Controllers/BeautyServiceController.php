<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BeautyServiceController
{
    public function __construct(
        private readonly ServiceService $serviceService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $services = BeautyService::where('status', 'active')
                ->with('salon', 'master')
                ->paginate(20);

            $correlationId = Str::uuid();
            Log::channel('audit')->info('Beauty services listed', [
                'count' => $services->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $services,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty service listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $service = BeautyService::with('salon', 'master', 'reviews')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            $service = DB::transaction(function () use ($correlationId) {
                return BeautyService::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'salon_id' => request('salon_id'),
                    'master_id' => request('master_id'),
                    'name' => request('name'),
                    'description' => request('description'),
                    'duration_minutes' => request('duration_minutes'),
                    'price' => request('price'),
                    'category' => request('category'),
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Beauty service created', [
                'service_id' => $service->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty service creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();
            $service = BeautyService::findOrFail($id);

            DB::transaction(function () use ($service, $correlationId) {
                $service->update([
                    'name' => request('name', $service->name),
                    'description' => request('description', $service->description),
                    'price' => request('price', $service->price),
                    'duration_minutes' => request('duration_minutes', $service->duration_minutes),
                    'status' => request('status', $service->status),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Beauty service updated', [
                'service_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty service update failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();
            $service = BeautyService::findOrFail($id);

            DB::transaction(function () use ($service, $correlationId) {
                $service->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                $service->delete();
            });

            Log::channel('audit')->info('Beauty service deleted', [
                'service_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty service deletion failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
