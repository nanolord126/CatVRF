<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Freelance\Events\PaymentMilestoneReleased;
use App\Domains\Freelance\Models\FreelanceContract;
use Illuminate\Support\Facades\DB;

final class ContractService
{
    public function releaseMilestonePayment(
        int $contractId,
        int $milestoneNumber,
        float $amount,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'releaseMilestonePayment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL releaseMilestonePayment', ['domain' => __CLASS__]);

        DB::transaction(function () use ($contractId, $milestoneNumber, $amount, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);

            $newAmountPaid = (float)$contract->amount_paid + $amount;
            $contract->update([
                'amount_paid' => $newAmountPaid,
                'amount_held_escrow' => max(0, (float)$contract->amount_held_escrow - $amount),
            ]);

            PaymentMilestoneReleased::dispatch($contract, $amount, $milestoneNumber, $correlationId);

            Log::channel('audit')->info('Freelance milestone payment released', [
                'contract_id' => $contractId,
                'milestone_number' => $milestoneNumber,
                'amount' => $amount,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function completeContract(
        int $contractId,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeContract'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeContract', ['domain' => __CLASS__]);

        DB::transaction(function () use ($contractId, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);
            $contract->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('audit')->info('Freelance contract completed', [
                'contract_id' => $contractId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function pauseContract(
        int $contractId,
        string $reason,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'pauseContract'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL pauseContract', ['domain' => __CLASS__]);

        DB::transaction(function () use ($contractId, $reason, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);
            $contract->update(['status' => 'on_hold']);

            Log::channel('audit')->info('Freelance contract paused', [
                'contract_id' => $contractId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function cancelContract(
        int $contractId,
        string $reason,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'cancelContract'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelContract', ['domain' => __CLASS__]);

        DB::transaction(function () use ($contractId, $reason, $correlationId) {
            $contract = FreelanceContract::findOrFail($contractId);
            $contract->update(['status' => 'cancelled']);

            Log::channel('audit')->info('Freelance contract cancelled', [
                'contract_id' => $contractId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
