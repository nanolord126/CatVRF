<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use App\Domains\Freelance\Models\FreelanceProposal;
use App\Domains\Freelance\Services\ProposalService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ProposalController
{
    public function __construct(
        private readonly ProposalService $proposalService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $proposal = $this->proposalService->submitProposal(
                jobId: $request->input('job_id'),
                freelancerId: auth()->user()->freelancer->id ?? 0,
                data: $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']),
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $proposal,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error submitting proposal', [
                'freelancer_id' => auth()->user()->freelancer->id ?? null,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit proposal',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $proposal = FreelanceProposal::findOrFail($id);

            $this->authorize('update', $proposal);

            $proposal->update($request->only(['proposed_amount', 'estimated_days', 'proposal_text']));

            $this->log->channel('audit')->info('Proposal updated', [
                'proposal_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $proposal,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error updating proposal', [
                'proposal_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update proposal',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $proposal = FreelanceProposal::findOrFail($id);

            $this->authorize('delete', $proposal);

            $this->proposalService->withdrawProposal($id, $correlationId);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error deleting proposal', [
                'proposal_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $contract,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error accepting proposal', [
                'proposal_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
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

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error rejecting proposal', [
                'proposal_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
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
                $q->where('user_id', auth()->id());
            })->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $proposals,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error listing my proposals', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $proposals,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error listing job proposals', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list proposals',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
