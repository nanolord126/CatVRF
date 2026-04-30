<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class FourEyesApprovalService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly CriticalOperationClassifier $classifier,
        private readonly ConflictDetector $conflictDetector,
        private readonly EscalationEngine $escalation,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Check if operation requires approval
     */
    public function requiresApproval(string $operationType, array $context): bool
    {
        if (! config('four_eyes.enabled', false)) {
            return false;
        }

        $classification = $this->classifier->classify($operationType, $context);

        return $classification['is_critical'];
    }

    /**
     * Create approval request
     */
    public function createApprovalRequest(
        string $operationType,
        int $requesterId,
        array $operationData,
        string $correlationId = ''
    ): ApprovalRequest {
        $this->fraudControl->check(
            userId: $requesterId,
            operationType: 'approval_request_create',
            amount: $operationData['amount'] ?? 0,
            correlationId: $correlationId,
        );

        $requester = User::findOrFail($requesterId);
        $classification = $this->classifier->classify($operationType, $operationData);

        return $this->db->transaction(function () use ($requester, $operationType, $operationData, $classification, $correlationId) {
            $approvalRequest = ApprovalRequest::create([
                'request_id' => (string) Str::uuid(),
                'requester_id' => $requester->id,
                'tenant_id' => $requester->tenant_id,
                'business_group_id' => $requester->business_group_id,
                'operation_type' => $operationType,
                'operation_data' => $operationData,
                'approval_level' => $classification['approval_level'],
                'approval_window_seconds' => $classification['approval_window_seconds'],
                'status' => 'pending',
                'expires_at' => CarbonImmutable::now()->addSeconds($classification['approval_window_seconds']),
                'correlation_id' => $correlationId,
                'requester_ip' => request()->ip(),
                'requester_user_agent' => request()->userAgent(),
            ]);

            // Log creation action
            ApprovalAction::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $requester->id,
                'action' => 'created',
                'action_data' => $operationData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => CarbonImmutable::now(),
            ]);

            // Audit log
            $this->audit->record(
                action: 'approval_request_created',
                subjectType: ApprovalRequest::class,
                subjectId: $approvalRequest->id,
                newValues: [
                    'operation_type' => $operationType,
                    'approval_level' => $classification['approval_level'],
                ],
                correlationId: $correlationId,
            );

            $this->log->$this->logger->info('Approval request created', [
                'approval_request_id' => $approvalRequest->id,
                'request_id' => $approvalRequest->request_id,
                'operation_type' => $operationType,
                'requester_id' => $requester->id,
            ]);

            return $approvalRequest;
        });
    }

    /**
     * Approve operation
     */
    public function approve(int $approvalRequestId, int $approverId, string $reason = ''): bool
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);

        $this->fraudControl->check(
            userId: $approverId,
            operationType: 'approval_approve',
            amount: $approvalRequest->operation_data['amount'] ?? 0,
            correlationId: $approvalRequest->correlation_id,
        );

        // Check conflicts
        $conflictCheck = $this->conflictDetector->hasConflict(
            $approvalRequest->requester_id,
            $approverId
        );

        if ($conflictCheck['has_conflict']) {
            $this->log->warning('Approval blocked due to conflict', [
                'approval_request_id' => $approvalRequestId,
                'approver_id' => $approverId,
                'conflicts' => $conflictCheck['conflicts'],
            ]);

            return false;
        }

        return $this->db->transaction(function () use ($approvalRequest, $approverId, $reason) {
            $approvalRequest->update([
                'status' => 'approved',
                'approver_id' => $approverId,
                'approval_reason' => $reason,
                'approved_at' => CarbonImmutable::now(),
            ]);

            // Log approval action
            ApprovalAction::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $approverId,
                'action' => 'approved',
                'comment' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => CarbonImmutable::now(),
            ]);

            // Audit log
            $this->audit->record(
                action: 'approval_request_approved',
                subjectType: ApprovalRequest::class,
                subjectId: $approvalRequest->id,
                newValues: [
                    'approver_id' => $approverId,
                    'reason' => $reason,
                ],
                correlationId: $approvalRequest->correlation_id,
            );

            $this->log->$this->logger->info('Approval request approved', [
                'approval_request_id' => $approvalRequest->id,
                'approver_id' => $approverId,
            ]);

            return true;
        });
    }

    /**
     * Reject operation
     */
    public function reject(int $approvalRequestId, int $approverId, string $reason): bool
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);

        $this->fraudControl->check(
            userId: $approverId,
            operationType: 'approval_reject',
            amount: 0,
            correlationId: $approvalRequest->correlation_id,
        );

        return $this->db->transaction(function () use ($approvalRequest, $approverId, $reason) {
            $approvalRequest->update([
                'status' => 'rejected',
                'approver_id' => $approverId,
                'rejection_reason' => $reason,
                'rejected_at' => CarbonImmutable::now(),
            ]);

            // Log rejection action
            ApprovalAction::create([
                'approval_request_id' => $approvalRequest->id,
                'user_id' => $approverId,
                'action' => 'rejected',
                'comment' => $reason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => CarbonImmutable::now(),
            ]);

            // Audit log
            $this->audit->record(
                action: 'approval_request_rejected',
                subjectType: ApprovalRequest::class,
                subjectId: $approvalRequest->id,
                newValues: [
                    'approver_id' => $approverId,
                    'reason' => $reason,
                ],
                correlationId: $approvalRequest->correlation_id,
            );

            $this->log->$this->logger->info('Approval request rejected', [
                'approval_request_id' => $approvalRequest->id,
                'approver_id' => $approverId,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Execute approved operation
     */
    public function execute(int $approvalRequestId): bool
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);

        if (! $approvalRequest->isApproved()) {
            $this->log->warning('Attempted to execute non-approved request', [
                'approval_request_id' => $approvalRequestId,
                'status' => $approvalRequest->status,
            ]);

            return false;
        }

        if ($approvalRequest->isExecuted()) {
            $this->log->$this->logger->info('Request already executed', [
                'approval_request_id' => $approvalRequestId,
            ]);

            return true;
        }

        // In a real implementation, this would execute the actual operation
        // For now, we'll mark it as executed
        $approvalRequest->update([
            'executed' => true,
            'executed_at' => CarbonImmutable::now(),
            'execution_result' => ['success' => true],
        ]);

        $this->log->$this->logger->info('Approved operation executed', [
            'approval_request_id' => $approvalRequestId,
            'operation_type' => $approvalRequest->operation_type,
        ]);

        return true;
    }

    /**
     * Get approval status
     */
    public function getStatus(int $approvalRequestId): array
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);

        return [
            'id' => $approvalRequest->id,
            'request_id' => $approvalRequest->request_id,
            'status' => $approvalRequest->status,
            'operation_type' => $approvalRequest->operation_type,
            'approval_level' => $approvalRequest->approval_level,
            'requester_id' => $approvalRequest->requester_id,
            'approver_id' => $approvalRequest->approver_id,
            'approved_at' => $approvalRequest->approved_at,
            'rejected_at' => $approvalRequest->rejected_at,
            'expires_at' => $approvalRequest->expires_at,
            'executed' => $approvalRequest->executed,
            'has_conflicts' => $approvalRequest->has_conflicts,
        ];
    }

    /**
     * Get pending approvals for user
     */
    public function getPendingApprovals(int $userId): array
    {
        $approvals = ApprovalRequest::pending()
            ->forApprover($userId)
            ->orderBy('created_at')
            ->get();

        return $approvals->map(function ($approval) {
            return [
                'id' => $approval->id,
                'request_id' => $approval->request_id,
                'operation_type' => $approval->operation_type,
                'operation_data' => $approval->operation_data,
                'approval_level' => $approval->approval_level,
                'requester_id' => $approval->requester_id,
                'created_at' => $approval->created_at,
                'expires_at' => $approval->expires_at,
            ];
        })->toArray();
    }

    /**
     * Escalate approval (timeout)
     */
    public function escalate(int $approvalRequestId): bool
    {
        $approvalRequest = ApprovalRequest::findOrFail($approvalRequestId);

        if (! $approvalRequest->isPending()) {
            $this->log->warning('Cannot escalate non-pending request', [
                'approval_request_id' => $approvalRequestId,
                'status' => $approvalRequest->status,
            ]);

            return false;
        }

        return $this->escalation->escalate($approvalRequest);
    }
}
