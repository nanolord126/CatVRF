<?php declare(strict_types=1);

namespace App\Services\Consulting;


use Illuminate\Http\Request;
use App\Models\Consulting\ConsultingContract;
use App\Models\Consulting\ConsultingProject;
use App\Models\Consulting\ConsultingSession;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class ConsultingProjectService
{

    /**
         * @param string $correlationId Unified audit trace.
         */
        public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

        /**
         * Start a project from a signed contract.
         */
        public function initializeProjectFromContract(int $contractId): ConsultingProject
        {
            $this->fraud->check($this->guard->id(), 'consulting_init_project', $this->request->ip());

            return $this->db->transaction(function() use ($contractId) {
                $contract = ConsultingContract::findOrFail($contractId);

                if (!$contract->isSigned()) {
                    throw new \LogicException('Contract must be signed before starting project.');
                }

                $this->logger->channel('audit')->info('Initializing Project from Contract', [
                    'correlation_id' => $this->correlationId(),
                    'contract_id' => $contractId,
                ]);

                $project = ConsultingProject::create([
                   'tenant_id' => $contract->tenant_id,
                   'consultant_id' => $contract->consultant_id,
                   'consulting_firm_id' => $contract->consulting_firm_id,
                   'client_id' => $contract->client_id,
                   'name' => "Project: Contract #{$contract->contract_number}",
                   'status' => 'active',
                   'start_date' => now(),
                   'budget' => $contract->total_amount,
                   'spent_budget' => 0,
                   'correlation_id' => $this->correlationId(),
                ]);

                return $project;
            });
        }

        /**
         * Log and fulfill a consulting session.
         */
        public function fulfillSession(int $sessionId, int $durationMinutes, string $notes): void
        {
            $this->fraud->check($this->guard->id(), 'consulting_fulfill_session', $this->request->ip());

            $this->db->transaction(function() use ($sessionId, $durationMinutes, $notes) {
                $session = ConsultingSession::findOrFail($sessionId);

                $this->logger->channel('audit')->info('Fulfilling Consulting Session', [
                    'session_id' => $sessionId,
                    'duration' => $durationMinutes,
                    'correlation_id' => $this->correlationId(),
                ]);

                $session->logSessionEnd($durationMinutes);
                $session->update(['session_notes' => $notes]);

                // If linked to a project, update spent budget
                if ($session->service->isHourly()) {
                    $costPerMinute = (int) ($session->consultant->hourly_rate / 60);
                    $sessionCost = $costPerMinute * $durationMinutes;

                    $project = ConsultingProject::where('client_id', $session->client_id)
                        ->where('consultant_id', $session->consultant_id)
                        ->active()
                        ->first();

                    if ($project) {
                        $project->updateProjectProgress($sessionCost);
                    }
                }
            });
        }

        /**
         * Terminate an active contract.
         */
        public function terminateContract(int $contractId, string $reason): void
        {
            $this->fraud->check($this->guard->id(), 'consulting_terminate_contract', $this->request->ip());

            $this->db->transaction(function() use ($contractId, $reason) {
                $contract = ConsultingContract::findOrFail($contractId);

                $this->logger->channel('audit')->warning('Terminating Consulting Contract', [
                    'contract_id' => $contractId,
                    'reason' => $reason,
                    'correlation_id' => $this->correlationId(),
                ]);

                $contract->update([
                   'status' => 'terminated',
                   'terms' => array_merge($contract->terms ?? [], [
                       'termination_reason' => $reason,
                       'terminated_at' => now()->toIso8601String()
                   ]),
                ]);

                // Also put linked project on hold
                if ($contract->project) {
                     $contract->project->update(['status' => 'on_hold']);
                }
            });
        }

        /**
         * Get active consulting load for a firm.
         */
        public function getFirmActiveLoad(int $firmId): Collection
        {
            return ConsultingProject::where('consulting_firm_id', $firmId)
                 ->active()
                 ->get();
        }
}
