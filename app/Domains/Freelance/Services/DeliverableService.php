<?php declare(strict_types=1);

namespace App\Domains\Freelance\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Freelance\Events\DeliverableSubmitted;
use App\Domains\Freelance\Models\FreelanceDeliverable;
use Illuminate\Support\Facades\DB;

final class DeliverableService
{
    public function submitDeliverable(
        int $contractId,
        int $freelancerId,
        array $data,
        string $correlationId,
    ): FreelanceDeliverable {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'submitDeliverable'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL submitDeliverable', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($contractId, $freelancerId, $data, $correlationId) {
            $deliverable = FreelanceDeliverable::create([
                'tenant_id' => tenant()->id,
                'contract_id' => $contractId,
                'freelancer_id' => $freelancerId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'files' => $data['files'] ?? [],
                'status' => 'submitted',
                'submitted_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            DeliverableSubmitted::dispatch($deliverable, $correlationId);

            Log::channel('audit')->info('Freelance deliverable submitted', [
                'deliverable_id' => $deliverable->id,
                'contract_id' => $contractId,
                'freelancer_id' => $freelancerId,
                'correlation_id' => $correlationId,
            ]);

            return $deliverable;
        });
    }

    public function approveDeliverable(
        int $deliverableId,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'approveDeliverable'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL approveDeliverable', ['domain' => __CLASS__]);

        DB::transaction(function () use ($deliverableId, $correlationId) {
            $deliverable = FreelanceDeliverable::findOrFail($deliverableId);
            $deliverable->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            Log::channel('audit')->info('Freelance deliverable approved', [
                'deliverable_id' => $deliverableId,
                'contract_id' => $deliverable->contract_id,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function requestRevision(
        int $deliverableId,
        string $feedback,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'requestRevision'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL requestRevision', ['domain' => __CLASS__]);

        DB::transaction(function () use ($deliverableId, $feedback, $correlationId) {
            $deliverable = FreelanceDeliverable::findOrFail($deliverableId);
            $deliverable->update([
                'status' => 'revisions_requested',
                'revision_notes' => $feedback,
                'revision_count' => $deliverable->revision_count + 1,
            ]);

            Log::channel('audit')->info('Revision requested for freelance deliverable', [
                'deliverable_id' => $deliverableId,
                'revision_count' => $deliverable->revision_count + 1,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function rejectDeliverable(
        int $deliverableId,
        string $reason,
        string $correlationId,
    ): void {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'rejectDeliverable'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL rejectDeliverable', ['domain' => __CLASS__]);

        DB::transaction(function () use ($deliverableId, $reason, $correlationId) {
            $deliverable = FreelanceDeliverable::findOrFail($deliverableId);
            $deliverable->update([
                'status' => 'rejected',
                'revision_notes' => $reason,
            ]);

            Log::channel('audit')->info('Freelance deliverable rejected', [
                'deliverable_id' => $deliverableId,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
