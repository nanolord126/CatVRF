<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\FourEyesApprovalService;
use App\Services\Security\CriticalOperationClassifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Log\LogManager;

final class RequireApprovalMiddleware
{
    public function __construct(
        private readonly FourEyesApprovalService $approvalService,
        private readonly CriticalOperationClassifier $classifier,
        private readonly Guard $auth,
        private readonly LogManager $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('four_eyes.enabled', false)) {
            return $next($request);
        }

        $userId = $this->auth->id();
        if (!$userId) {
            return $next($request);
        }

        $operationType = $this->getOperationType($request);
        $context = $this->getContext($request);

        // Check if operation requires approval
        if (!$this->classifier->isCritical($operationType, $context)) {
            return $next($request); // Not critical, proceed
        }

        // Check for existing approval
        $approvalId = $request->header('X-Approval-Id');
        if ($approvalId) {
            $status = $this->approvalService->getStatus((int) $approvalId);

            if ($status['status'] === 'approved') {
                // Execute approved operation
                $this->approvalService->execute((int) $approvalId);
                return $next($request);
            } elseif ($status['status'] === 'rejected') {
                return $this->handleRejectedOperation($request, $status);
            } elseif ($status['status'] === 'expired') {
                return $this->handleExpiredApproval($request);
            }
        }

        // No approval, create request
        $approvalRequest = $this->approvalService->createApprovalRequest(
            $operationType,
            $userId,
            $context,
            $request->header('X-Correlation-ID') ?? '',
        );

        return response()->json([
            'error' => 'approval_required',
            'approval_request_id' => $approvalRequest->id,
            'request_id' => $approvalRequest->request_id,
            'approval_level' => $approvalRequest->approval_level,
            'approval_window_seconds' => $approvalRequest->approval_window_seconds,
            'expires_at' => $approvalRequest->expires_at,
            'message' => 'This operation requires approval',
        ], 403);
    }

    /**
     * Get operation type from request
     */
    private function getOperationType(Request $request): string
    {
        $route = $request->route();
        $routeName = $route?->getName() ?? $request->path();

        // Map route names to operation types
        $operationMap = [
            'wallet.withdraw' => 'wallet_withdrawal',
            'products.mass-edit' => 'mass_product_edit',
            'users.role-change' => 'user_role_change',
            'payment-gateway.change' => 'payment_gateway_change',
            'api-keys.create' => 'api_key_creation',
        ];

        return $operationMap[$routeName] ?? $routeName;
    }

    /**
     * Get context from request
     */
    private function getContext(Request $request): array
    {
        $context = [
            'amount' => $request->input('amount', 0),
            'user_type' => $request->input('user_type', 'individual'),
            'value' => $request->input('value', 0),
            'count' => $request->input('count', 0),
            'current_role' => $this->auth->user()?->roles->first()?->name,
        ];

        // Add route-specific context
        if ($request->route()->getName() === 'wallet.withdraw') {
            $context['amount'] = $request->input('amount');
            $context['user_type'] = $this->auth->user()?->user_type ?? 'individual';
        }

        if ($request->route()->getName() === 'products.mass-edit') {
            $context['count'] = $request->input('product_count', 0);
        }

        if ($request->route()->getName() === 'users.role-change') {
            $context['current_role'] = $this->auth->user()?->roles->first()?->name;
            $context['target_role'] = $request->input('role');
        }

        return $context;
    }

    /**
     * Handle rejected operation
     */
    private function handleRejectedOperation(Request $request, array $status): Response
    {
        $this->log->info('Operation rejected by approver', [
            'approval_request_id' => $status['id'],
            'user_id' => $this->auth->id(),
        ]);

        return response()->json([
            'error' => 'operation_rejected',
            'message' => 'This operation was rejected by an approver',
            'approval_request_id' => $status['id'],
            'rejected_at' => $status['rejected_at'],
        ], 403);
    }

    /**
     * Handle expired approval
     */
    private function handleExpiredApproval(Request $request): Response
    {
        $this->log->info('Approval expired', [
            'user_id' => $this->auth->id(),
        ]);

        return response()->json([
            'error' => 'approval_expired',
            'message' => 'The approval for this operation has expired. Please request a new approval.',
        ], 403);
    }
}
