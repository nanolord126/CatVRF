<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ProposalController extends Controller
{

    public function __construct(
            private readonly ProposalService $proposalService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $proposal = $this->proposalService->submitProposal(
                    jobId: $request->input('job_id'),
                    freelancerId: $request->user()->freelancer->id ?? 0,
                    data: $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                    correlationId: $correlationId,
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $proposal,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Error submitting proposal', [
                    'freelancer_id' => $request->user()->freelancer->id ?? null,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to submit proposal',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $proposal = FreelanceProposal::findOrFail($id);

                $this->authorize('update', $proposal);

                $proposal->update($request->only(['proposed_amount', 'estimated_days', 'proposal_text']));

                $this->logger->info('Proposal updated', [
                    'proposal_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $proposal,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error updating proposal', [
                    'proposal_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update proposal',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $proposal = FreelanceProposal::findOrFail($id);

                $this->authorize('delete', $proposal);

                $this->proposalService->withdrawProposal($id, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error deleting proposal', [
                    'proposal_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete proposal',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function accept(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $proposal = FreelanceProposal::findOrFail($id);

                $this->authorize('accept', $proposal);

                $contract = $this->proposalService->acceptProposal($id, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $contract,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error accepting proposal', [
                    'proposal_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to accept proposal',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function reject(int $id): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $proposal = FreelanceProposal::findOrFail($id);

                $this->authorize('reject', $proposal);

                $this->proposalService->rejectProposal($id, null, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error rejecting proposal', [
                    'proposal_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to reject proposal',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myProposals(): JsonResponse
        {
            try {
                $proposals = FreelanceProposal::whereHas('freelancer', function ($q) {
                    $q->where('user_id', $request->user()?->id);
                })->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $proposals,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing my proposals', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list proposals',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function jobProposals(int $jobId): JsonResponse
        {
            try {
                $proposals = FreelanceProposal::where('job_id', $jobId)->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $proposals,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing job proposals', [
                    'job_id' => $jobId,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list proposals',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
