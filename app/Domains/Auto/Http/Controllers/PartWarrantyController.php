<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PartWarrantyController extends Model
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
                $warranties = PartWarranty::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->client_id, fn($q) => $q->where('client_id', $request->client_id))
                    ->when($request->claim_status, fn($q) => $q->where('claim_status', $request->claim_status))
                    ->with(['part', 'client', 'order'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $warranties,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Part warranty index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve part warranties',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'auto_part_id' => 'required|exists:auto_parts,id',
                'auto_part_order_id' => 'required|exists:auto_part_orders,id',
                'client_id' => 'required|exists:users,id',
                'warranty_type' => 'required|in:manufacturer,dealer,extended',
                'warranty_months' => 'required|integer|min:1|max:60',
                'start_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            try {
                $warranty = $this->warrantyService->createPartWarranty($validated);

                Log::channel('audit')->info('Part warranty created', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $warranty->load(['part', 'client']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Part warranty creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create part warranty',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(PartWarranty $warranty): JsonResponse
        {
            return response()->json([
                'success' => true,
                'data' => $warranty->load(['part', 'client', 'order', 'replacementPart']),
            ]);
        }

        public function claim(Request $request, PartWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'claim_reason' => 'required|string',
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->submitPartWarrantyClaim(
                    $warranty->id,
                    $validated['claim_reason'],
                    $validated['notes'] ?? null
                );

                Log::channel('audit')->info('Part warranty claim submitted', [
                    'correlation_id' => $correlationId,
                    'warranty_id' => $warranty->id,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['part', 'client']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Part warranty claim failed', [
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

        public function approve(Request $request, PartWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'replacement_part_id' => 'nullable|exists:auto_parts,id',
                'notes' => 'nullable|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->approvePartWarrantyClaim(
                    $warranty->id,
                    $validated['replacement_part_id'] ?? null,
                    $validated['notes'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['part', 'client', 'replacementPart']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Part warranty approval failed', [
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

        public function reject(Request $request, PartWarranty $warranty): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'notes' => 'required|string',
            ]);

            try {
                $updatedWarranty = $this->warrantyService->rejectPartWarrantyClaim(
                    $warranty->id,
                    $validated['notes']
                );

                return response()->json([
                    'success' => true,
                    'data' => $updatedWarranty->fresh(['part', 'client']),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Part warranty rejection failed', [
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
