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
        private readonly FraudControlService $fraudControlService,) {}

    public function index(): JsonResponse
    {
        try {
            $services = BeautyService::where('status', 'active')
                ->with('salon', 'master')
                ->paginate(20);

            $correlationId = Str::uuid()->toString();
            $this->log->channel('audit')->info('Beauty services listed', [
                'count' => $services->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $services,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty service listing failed', [
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
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $correlationId = Str::uuid()->toString();

            $service = $this->db->transaction(function () use ($correlationId) {
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

            $this->log->channel('audit')->info('Beauty service created', [
                'service_id' => $service->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty service creation failed', [
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
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $correlationId = Str::uuid()->toString();
            $service = BeautyService::findOrFail($id);

            $this->db->transaction(function () use ($service, $correlationId) {
                $service->update([
                    'name' => request('name', $service->name),
                    'description' => request('description', $service->description),
                    'price' => request('price', $service->price),
                    'duration_minutes' => request('duration_minutes', $service->duration_minutes),
                    'status' => request('status', $service->status),
                    'correlation_id' => $correlationId,
                ]);
            });

            $this->log->channel('audit')->info('Beauty service updated', [
                'service_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $service,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty service update failed', [
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
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $correlationId = Str::uuid()->toString();
            $service = BeautyService::findOrFail($id);

            $this->db->transaction(function () use ($service, $correlationId) {
                $service->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                $service->delete();
            });

            $this->log->channel('audit')->info('Beauty service deleted', [
                'service_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            $this->log->error('Beauty service deletion failed', [
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
