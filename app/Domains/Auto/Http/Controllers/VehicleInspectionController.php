<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VehicleInspectionController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControl
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $inspections = VehicleInspection::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                    ->with(['client', 'vehicle', 'inspector'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $inspections,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve vehicle inspections',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'client_id' => 'required|exists:users,id',
                'inspection_type' => 'required|in:periodic,pre_purchase,insurance,custom',
                'scheduled_at' => 'required|date|after:now',
                'inspector_id' => 'required|exists:users,id',
                'price' => 'required|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            try {
                $this->fraudControl->check('vehicle_inspection_booking', $request->ip(), [
                    'user_id' => auth()->id(),
                    'amount' => $validated['price'],
                ]);

                $inspection = DB::transaction(function () use ($validated, $correlationId) {
                    return VehicleInspection::create([
                        ...$validated,
                        'tenant_id' => tenant()->id,
                        'status' => 'scheduled',
                        'payment_status' => 'pending',
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                    ]);
                });

                Log::channel('audit')->info('Vehicle inspection created', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $inspection->load(['client', 'vehicle', 'inspector']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(VehicleInspection $inspection): JsonResponse
        {
            return response()->json([
                'success' => true,
                'data' => $inspection->load(['client', 'vehicle', 'inspector']),
            ]);
        }

        public function pass(VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                DB::transaction(function () use ($inspection) {
                    $inspection->update([
                        'status' => 'completed',
                        'result' => 'passed',
                        'completed_at' => now(),
                        'expires_at' => now()->addMonths(12),
                        'certificate_number' => 'CERT-' . Str::uuid()->toString(),
                    ]);
                });

                Log::channel('audit')->info('Vehicle inspection passed', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $inspection->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection pass failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to pass vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function fail(VehicleInspection $inspection, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            try {
                DB::transaction(function () use ($inspection, $validated) {
                    $inspection->update([
                        'status' => 'completed',
                        'result' => 'failed',
                        'completed_at' => now(),
                        'notes' => $validated['notes'],
                    ]);
                });

                Log::channel('audit')->info('Vehicle inspection failed', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $inspection->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection fail recording failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record vehicle inspection failure',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function update(Request $request, VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'scheduled_at' => 'sometimes|date|after:now',
                'inspector_id' => 'sometimes|exists:users,id',
                'notes' => 'nullable|string',
            ]);

            try {
                DB::transaction(function () use ($inspection, $validated) {
                    $inspection->update($validated);
                });

                Log::channel('audit')->info('Vehicle inspection updated', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $inspection->fresh(['client', 'vehicle', 'inspector']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection update failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(VehicleInspection $inspection): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $inspection->delete();

                Log::channel('audit')->info('Vehicle inspection deleted', [
                    'correlation_id' => $correlationId,
                    'inspection_id' => $inspection->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle inspection deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Vehicle inspection deletion failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete vehicle inspection',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
