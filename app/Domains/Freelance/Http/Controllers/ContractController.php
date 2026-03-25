<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;

use App\Domains\Freelance\Models\FreelanceContract;
use App\Domains\Freelance\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ContractController
{
    public function __construct(
        private readonly ContractService $contractService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $contracts = FreelanceContract::where('status', '!=', 'cancelled')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $contracts,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error listing contracts', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list contracts',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $contract = FreelanceContract::with(['freelancer', 'client', 'deliverables'])->findOrFail($id);

            $this->authorize('view', $contract);

            return response()->json([
                'success' => true,
                'data' => $contract,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error showing contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Contract not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function releaseMilestone(Request $request, int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $contract = FreelanceContract::findOrFail($id);

            $this->authorize('release', $contract);

            $this->contractService->releaseMilestonePayment(
                contractId: $id,
                milestoneNumber: $request->input('milestone_number', 1),
                amount: $request->input('amount'),
                correlationId: $correlationId,
            );

            $this->log->channel('audit')->info('Milestone payment released', [
                'contract_id' => $id,
                'amount' => $request->input('amount'),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error releasing milestone', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to release milestone',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $contract = FreelanceContract::findOrFail($id);

            $this->authorize('complete', $contract);

            $this->contractService->completeContract($id, $correlationId);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error completing contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete contract',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function pause(Request $request, int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $contract = FreelanceContract::findOrFail($id);

            $this->authorize('pause', $contract);

            $this->contractService->pauseContract($id, $request->input('reason', ''), $correlationId);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error pausing contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to pause contract',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $contract = FreelanceContract::findOrFail($id);

            $this->authorize('cancel', $contract);

            $this->contractService->cancelContract($id, $request->input('reason', ''), $correlationId);

            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error cancelling contract', [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel contract',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function myContracts(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $contracts = FreelanceContract::where(function ($q) use ($userId) {
                $q->whereHas('freelancer', fn($q) => $q->where('user_id', $userId))
                  ->orWhere('client_id', $userId);
            })->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $contracts,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error listing my contracts', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to list contracts',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }

    public function earningsReport(): JsonResponse
    {
        try {
            $totalEarnings = FreelanceContract::where('status', 'completed')
                ->sum($this->db->raw('amount_paid'));

            $activeContracts = FreelanceContract::where('status', 'active')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_earnings' => $totalEarnings,
                    'active_contracts' => $activeContracts,
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Error getting earnings report', [
                'error' => $e->getMessage(),
                'correlation_id' => Str::uuid(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get earnings report',
                'correlation_id' => Str::uuid(),
            ], 500);
        }
    }
}
