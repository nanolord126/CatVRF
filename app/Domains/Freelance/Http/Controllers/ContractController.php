<?php declare(strict_types=1);

namespace App\Domains\Freelance\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ContractController extends Controller
{

    public function __construct(private readonly ContractService $contractService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $contracts = FreelanceContract::where('status', '!=', 'cancelled')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $contracts,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing contracts', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $contract,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error showing contract', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                $this->logger->info('Milestone payment released', [
                    'contract_id' => $id,
                    'amount' => $request->input('amount'),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error releasing milestone', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error completing contract', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error pausing contract', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error cancelling contract', [
                    'contract_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to cancel contract',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myContracts(): JsonResponse
        {
            try {
                $userId = $request->user()?->id;

                $contracts = FreelanceContract::where(function ($q) use ($userId) {
                    $q->whereHas('freelancer', fn($q) => $q->where('user_id', $userId))
                      ->orWhere('client_id', $userId);
                })->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $contracts,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error listing my contracts', [
                    'user_id' => $request->user()?->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'total_earnings' => $totalEarnings,
                        'active_contracts' => $activeContracts,
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Error getting earnings report', [
                    'error' => $e->getMessage(),
                    'correlation_id' => Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to get earnings report',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
