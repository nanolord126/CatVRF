<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Services\SalonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BeautySalonController
{
    public function __construct(
        private readonly SalonService $salonService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $salons = BeautySalon::where('is_active', true)
                ->with('masters', 'services')
                ->paginate(20);

            $correlationId = Str::uuid();
            Log::channel('audit')->info('Beauty salons listed', [
                'count' => $salons->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $salons,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty salon listing failed', [
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
            $salon = BeautySalon::with('masters', 'services', 'reviews')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $salon,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Salon not found',
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

            $salon = DB::transaction(function () use ($correlationId) {
                return BeautySalon::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'name' => request('name'),
                    'description' => request('description'),
                    'address' => request('address'),
                    'phone' => request('phone'),
                    'email' => request('email'),
                    'owner_id' => auth()->id(),
                    'schedule' => request('schedule', []),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Beauty salon created', [
                'salon_id' => $salon->id,
                'owner_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $salon,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty salon creation failed', [
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
            $salon = BeautySalon::findOrFail($id);

            DB::transaction(function () use ($salon, $correlationId) {
                $salon->update([
                    'name' => request('name', $salon->name),
                    'description' => request('description', $salon->description),
                    'phone' => request('phone', $salon->phone),
                    'schedule' => request('schedule', $salon->schedule),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('Beauty salon updated', [
                'salon_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $salon,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid();
            Log::error('Beauty salon update failed', [
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
