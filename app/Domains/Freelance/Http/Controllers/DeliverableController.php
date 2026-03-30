<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliverableController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly DeliverableService $deliverableService,
            private readonly FraudControlService $fraudControlService,
        ) {}


        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(Auth::user(), 'deliverable_create', crc32((string)$correlationId));

            try {
                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'file_urls' => 'nullable|array',
                ]);

                $deliverable = $this->deliverableService->submitDeliverable(
                    contractId: $request->input('contract_id'),
                    freelancerId: Auth::user()?->freelancer->id ?? 0,
                    data: $validated,
                    correlationId: (string)$correlationId,
                );

                return response()->json([
                    'success' => true,
                    'data' => $deliverable,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error submitting deliverable', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to submit deliverable',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $deliverable = FreelanceDeliverable::findOrFail($id);

                $this->authorize('view', $deliverable);

                return response()->json([
                    'success' => true,
                    'data' => $deliverable,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error showing deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Deliverable not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function approve(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $deliverable = FreelanceDeliverable::findOrFail($id);

                $this->authorize('approve', $deliverable);

                $this->deliverableService->approveDeliverable($id, (string)$correlationId);

                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error approving deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve deliverable',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function requestRevision(Request $request, int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $deliverable = FreelanceDeliverable::findOrFail($id);

                $this->authorize('requestRevision', $deliverable);

                $this->deliverableService->requestRevision(
                    $id,
                    $request->input('feedback', ''),
                    (string)$correlationId
                );

                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error requesting revision', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to request revision',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function reject(Request $request, int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $deliverable = FreelanceDeliverable::findOrFail($id);

                $this->authorize('reject', $deliverable);

                $this->deliverableService->rejectDeliverable(
                    $id,
                    $request->input('reason', ''),
                    (string)$correlationId
                );

                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error rejecting deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject deliverable',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function contractDeliverables(int $contractId): JsonResponse
        {
            try {

                $deliverables = FreelanceDeliverable::where('contract_id', $contractId)
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $deliverables,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Error listing contract deliverables', [
                    'contract_id' => $contractId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list deliverables',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
