<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class DeliverableController extends Controller
{

    public function __construct(
            private readonly DeliverableService $deliverableService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'deliverable_create', amount: 0, correlationId: $correlationId ?? '');

            try {
                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'file_urls' => 'nullable|array',
                ]);

                $deliverable = $this->deliverableService->submitDeliverable(
                    contractId: $request->input('contract_id'),
                    freelancerId: $request->user()?->freelancer->id ?? 0,
                    data: $validated,
                    correlationId: (string)$correlationId,
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $deliverable,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Error submitting deliverable', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $deliverable,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error showing deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error approving deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error requesting revision', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error rejecting deliverable', [
                    'deliverable_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $deliverables,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing contract deliverables', [
                    'contract_id' => $contractId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list deliverables',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
