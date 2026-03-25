<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Freelance\Events\ProposalAccepted;
use App\Domains\Freelance\Models\FreelanceContract;
use App\Domains\Freelance\Models\FreelanceJob;
use App\Domains\Freelance\Models\FreelanceProposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProposalService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function submitProposal(
        int $jobId,
        int $freelancerId,
        array $data,
        string $correlationId,
    ): FreelanceProposal {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($jobId, $freelancerId, $data, $correlationId) {
            $job = FreelanceJob::findOrFail($jobId);

            $proposal = FreelanceProposal::create([
                'tenant_id' => tenant()->id,
                'job_id' => $jobId,
                'freelancer_id' => $freelancerId,
                'proposed_amount' => $data['proposed_amount'],
                'commission_amount' => (float)$data['proposed_amount'] * 0.14,
                'estimated_days' => $data['estimated_days'] ?? null,
                'proposal_text' => $data['proposal_text'],
                'status' => 'pending',
                'correlation_id' => $correlationId,
            ]);

            $job->increment('proposals_count');

            $this->log->channel('audit')->info('Freelance proposal submitted', [
                'proposal_id' => $proposal->id,
                'job_id' => $jobId,
                'freelancer_id' => $freelancerId,
                'proposed_amount' => $data['proposed_amount'],
                'commission_amount' => $proposal->commission_amount,
                'correlation_id' => $correlationId,
            ]);

            return $proposal;
        });
    }

    public function acceptProposal(
        int $proposalId,
        string $correlationId,
    ): FreelanceContract {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($proposalId, $correlationId) {
            $proposal = FreelanceProposal::with(['job', 'freelancer'])->findOrFail($proposalId);

            $proposal->update(['status' => 'accepted', 'responded_at' => now()]);

            $contract = FreelanceContract::create([
                'tenant_id' => tenant()->id,
                'job_id' => $proposal->job_id,
                'freelancer_id' => $proposal->freelancer_id,
                'client_id' => $proposal->job->client_id,
                'contract_number' => 'FC-' . Str::upper(Str::random(12)),
                'agreed_amount' => $proposal->proposed_amount,
                'commission_amount' => $proposal->commission_amount,
                'duration_days' => $proposal->estimated_days,
                'payment_type' => $proposal->job->pricing_type,
                'status' => 'active',
                'start_date' => now(),
                'correlation_id' => $correlationId,
            ]);

            ProposalAccepted::dispatch($proposal, $correlationId);

            $this->log->channel('audit')->info('Freelance proposal accepted and contract created', [
                'proposal_id' => $proposalId,
                'contract_id' => $contract->id,
                'freelancer_id' => $proposal->freelancer_id,
                'client_id' => $proposal->job->client_id,
                'agreed_amount' => $proposal->proposed_amount,
                'correlation_id' => $correlationId,
            ]);

            return $contract;
        });
    }

    public function rejectProposal(
        int $proposalId,
        ?string $reason = null,
        string $correlationId = '',
    ): void {


                $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($proposalId, $reason, $correlationId) {
            $proposal = FreelanceProposal::findOrFail($proposalId);
            $proposal->update(['status' => 'rejected', 'responded_at' => now()]);

            $this->log->channel('audit')->info('Freelance proposal rejected', [
                'proposal_id' => $proposalId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function withdrawProposal(
        int $proposalId,
        string $correlationId,
    ): void {


                $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($proposalId, $correlationId) {
            $proposal = FreelanceProposal::findOrFail($proposalId);
            $proposal->update(['status' => 'cancelled']);

            $this->log->channel('audit')->info('Freelance proposal withdrawn', [
                'proposal_id' => $proposalId,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
