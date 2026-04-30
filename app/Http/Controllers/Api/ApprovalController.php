<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Services\Security\FourEyesApprovalService;
use App\Services\Security\ConflictDetector;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthManager;
use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;

final class ApprovalController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly FourEyesApprovalService $approvalService,
        private readonly ConflictDetector $conflictDetector,
    ) {}

    /**
     * Get my pending approvals
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $approvals = $this->approvalService->getPendingApprovals($userId);

        return new JsonResponse([
            'approvals' => $approvals,
            'count' => count($approvals),
        ]);
    }

    /**
     * Get my approval requests
     */
    public function getMyRequests(Request $request): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $requests = ApprovalRequest::forRequester($userId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'request_id' => $request->request_id,
                    'operation_type' => $request->operation_type,
                    'status' => $request->status,
                    'approval_level' => $request->approval_level,
                    'created_at' => $request->created_at,
                    'expires_at' => $request->expires_at,
                ];
            });

        return new JsonResponse([
            'requests' => $requests,
            'count' => $requests->count(),
        ]);
    }

    /**
     * Get approval request details
     */
    public function show(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $approval = ApprovalRequest::with(['requester', 'approver', 'actions'])
            ->findOrFail($approvalRequest);

        // Check if user is requester or approver
        if ($approval->requester_id !== $userId && $approval->approver_id !== $userId) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        return new JsonResponse([
            'id' => $approval->id,
            'request_id' => $approval->request_id,
            'operation_type' => $approval->operation_type,
            'operation_data' => $approval->operation_data,
            'approval_level' => $approval->approval_level,
            'status' => $approval->status,
            'requester' => [
                'id' => $approval->requester->id,
                'name' => $approval->requester->name,
                'email' => $approval->requester->email,
            ],
            'approver' => $approval->approver ? [
                'id' => $approval->approver->id,
                'name' => $approval->approver->name,
                'email' => $approval->approver->email,
            ] : null,
            'approved_at' => $approval->approved_at,
            'rejected_at' => $approval->rejected_at,
            'expires_at' => $approval->expires_at,
            'has_conflicts' => $approval->has_conflicts,
            'conflicts_detected' => $approval->conflicts_detected,
            'executed' => $approval->executed,
            'created_at' => $approval->created_at,
            'actions' => $approval->actions->map(function ($action) {
                return [
                    'action' => $action->action,
                    'comment' => $action->comment,
                    'user_id' => $action->user_id,
                    'created_at' => $action->created_at,
                ];
            }),
        ]);
    }

    /**
     * Approve request
     */
    public function approve(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->approvalService->approve(
            $approvalRequest,
            $userId,
            $validated['reason'] ?? '',
        );

        if ($result) {
            return new JsonResponse([
                'message' => 'Approval request approved successfully',
            ]);
        } else {
            return new JsonResponse([
                'error' => 'approval_failed',
                'message' => 'Failed to approve request. Possible conflict detected.',
            ], 400);
        }
    }

    /**
     * Reject request
     */
    public function reject(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $result = $this->approvalService->reject(
            $approvalRequest,
            $userId,
            $validated['reason'],
        );

        if ($result) {
            return new JsonResponse([
                'message' => 'Approval request rejected successfully',
            ]);
        } else {
            return new JsonResponse([
                'error' => 'rejection_failed',
                'message' => 'Failed to reject request.',
            ], 400);
        }
    }

    /**
     * Comment on request
     */
    public function comment(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $approval = ApprovalRequest::findOrFail($approvalRequest);

        ApprovalAction::create([
            'approval_request_id' => $approval->id,
            'user_id' => $userId,
            'action' => 'commented',
            'comment' => $validated['comment'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => CarbonImmutable::now(),
        ]);

        return new JsonResponse([
            'message' => 'Comment added successfully',
        ]);
    }

    /**
     * Get eligible approvers
     */
    public function getEligibleApprovers(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $approval = ApprovalRequest::findOrFail($approvalRequest);

        if ($approval->requester_id !== $userId) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $requiredRoles = config('four_eyes.critical_operations.'.$approval->operation_type.'.required_roles', ['admin']);
        $eligibleApprovers = $this->conflictDetector->getEligibleApprovers($approval->requester_id, $requiredRoles);

        return new JsonResponse([
            'eligible_approvers' => $eligibleApprovers,
            'count' => count($eligibleApprovers),
        ]);
    }

    /**
     * Escalate request (manual)
     */
    public function escalate(Request $request, int $approvalRequest): JsonResponse
    {
        $userId = $this->auth->id();

        if (! $userId) {
            return new JsonResponse(['error' => 'unauthenticated'], 401);
        }

        $result = $this->approvalService->escalate($approvalRequest);

        if ($result) {
            return new JsonResponse([
                'message' => 'Approval request escalated successfully',
            ]);
        } else {
            return new JsonResponse([
                'error' => 'escalation_failed',
                'message' => 'Failed to escalate request.',
            ], 400);
        }
    }
}
