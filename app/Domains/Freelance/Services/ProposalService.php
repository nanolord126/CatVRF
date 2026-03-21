<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Freelance\Events\ProposalAccepted;
use App\Domains\Freelance\Models\FreelanceContract;
use App\Domains\Freelance\Models\FreelanceJob;
use App\Domains\Freelance\Models\FreelanceProposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProposalService
{
    public function submitProposal(
        int $jobId,
        int $freelancerId,
        array $data,
        string $correlationId,
    ): FreelanceProposal {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'submitProposal'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL submitProposal', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($jobId, $freelancerId, $data, $correlationId) {
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

            Log::channel('audit')->info('Freelance proposal submitted', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'acceptProposal'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL acceptProposal', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($proposalId, $correlationId) {
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

            Log::channel('audit')->info('Freelance proposal accepted and contract created', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'rejectProposal'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL rejectProposal', ['domain' => __CLASS__]);

        DB::transaction(function () use ($proposalId, $reason, $correlationId) {
            $proposal = FreelanceProposal::findOrFail($proposalId);
            $proposal->update(['status' => 'rejected', 'responded_at' => now()]);

            Log::channel('audit')->info('Freelance proposal rejected', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'withdrawProposal'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL withdrawProposal', ['domain' => __CLASS__]);

        DB::transaction(function () use ($proposalId, $correlationId) {
            $proposal = FreelanceProposal::findOrFail($proposalId);
            $proposal->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Freelance proposal withdrawn', [
                'proposal_id' => $proposalId,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
