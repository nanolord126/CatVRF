<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceWarrantyController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly WarrantyService $warrantyService
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $warranties = ServiceWarranty::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                    ->when($request->claim_status, fn($q) => $q->where('claim_status', $request->claim_status))
                    ->with(['serviceOrder', 'client', 'vehicle'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $warranties,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Service warranty index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve service warranties',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'auto_service_order_id' => 'required|exists:auto_service_orders,id',
                'client_id' => 'required|exists:users,id',
                'vehicle_id' => 'required|exists:vehicles,id',
                'warranty_type' => 'required|in:standard,extended,lifetime',
                'warranty_months' => 'required|integer|min:1|max:60',
                'warranty_km' => 'nullable|integer|min:1000',
                'start_date' => 'required|date',
                'start_mileage' => 'required|integer|min:0',
                'repair_description' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            try {
                $warranty = $this->warrantyService->createServiceWarranty($validated);

                Log::channel('audit')->info('Service warranty created', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $warranty->load(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Service warranty creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create service warranty',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(ServiceWarranty $warranty): JsonResponse
        {
            return response()->json([
                'success' => true,
                'data' => $warranty->load(['serviceOrder', 'client', 'vehicle']),
            ]);
        }

        public function claim(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'claim_reason' => 'required|string',
                'claim_mileage' => 'required|integer|min:0',
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->submitServiceWarrantyClaim(
                    $warranty->id,
                    $validated['claim_reason'],
                    $validated['claim_mileage'],
                    $validated['notes'] ?? null
                );

                Log::channel('audit')->info('Service warranty claim submitted', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Service warranty claim failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }

        public function approve(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->approveServiceWarrantyClaim(
                    $warranty->id,
                    $validated['notes'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Service warranty approval failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve warranty claim',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function reject(Request $request, ServiceWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->rejectServiceWarrantyClaim(
                    $warranty->id,
                    $validated['notes']
                );

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['serviceOrder', 'client', 'vehicle']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Service warranty rejection failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject warranty claim',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
