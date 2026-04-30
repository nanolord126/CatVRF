<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Models\InsiderThreatLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\EmployeeDeprovisionService;
use App\Services\Security\InsiderThreatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;

final class ZeroTrustSecurityController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly EmployeeDeprovisionService $deprovisionService,
        private readonly InsiderThreatService $insiderThreatService,
    ) {}

    /**
     * Revoke employee access from tenant
     */
    public function revokeEmployee(Request $request, Tenant $tenant, User $employee): JsonResponse
    {
        $this->authorize('update', $tenant);

        $request->validate([
            'reason' => 'required|string|max:500',
            'requires_multi_owner_confirmation' => 'boolean',
        ]);

        $result = $this->deprovisionService->revokeAccess(
            employee: $employee,
            tenant: $tenant,
            revokedBy: $this->auth->user(),
            reason: $request->input('reason'),
            requiresMultiOwnerConfirmation: $request->boolean('requires_multi_owner_confirmation', false),
        );

        if (! $result->success) {
            return new JsonResponse([
                'message' => $result->error,
                'error' => 'deprovision_failed',
            ], 400);
        }

        return new JsonResponse([
            'message' => 'Employee access revoked successfully',
            'details' => $result->details,
        ]);
    }

    /**
     * Store multi-owner confirmation for employee revocation
     */
    public function storeMultiOwnerConfirmation(Request $request, Tenant $tenant, User $employee): JsonResponse
    {
        $this->authorize('update', $tenant);

        $this->deprovisionService->storeMultiOwnerConfirmation(
            tenant: $tenant,
            confirmingOwner: $this->auth->user(),
            requester: $employee,
        );

        return new JsonResponse([
            'message' => 'Multi-owner confirmation stored',
        ]);
    }

    /**
     * Restore employee access
     */
    public function restoreEmployee(Request $request, Tenant $tenant, User $employee): JsonResponse
    {
        $this->authorize('update', $tenant);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $result = $this->deprovisionService->restoreAccess(
            employee: $employee,
            tenant: $tenant,
            restoredBy: $this->auth->user(),
            reason: $request->input('reason'),
        );

        if (! $result->success) {
            return new JsonResponse([
                'message' => $result->error,
                'error' => 'restore_failed',
            ], 400);
        }

        return new JsonResponse([
            'message' => 'Employee access restored successfully',
            'details' => $result->details,
        ]);
    }

    /**
     * Get threat summary for tenant
     */
    public function getThreatSummary(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $days = $request->integer('days', 30);
        $summary = $this->insiderThreatService->getThreatSummary($tenant, $days);

        return new JsonResponse($summary);
    }

    /**
     * Get high-risk threats for tenant
     */
    public function getHighRiskThreats(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $days = $request->integer('days', 7);
        $threats = $this->insiderThreatService->getHighRiskThreats($tenant, $days);

        return new JsonResponse([
            'data' => $threats->map(fn ($log) => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user->getFullName(),
                'user_email' => $log->user->email,
                'action_type' => $log->action_type,
                'anomaly_score' => $log->anomaly_score,
                'severity' => $log->severity,
                'was_blocked' => $log->was_blocked,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get user threat profile
     */
    public function getUserThreatProfile(Request $request, Tenant $tenant, User $user): JsonResponse
    {
        $this->authorize('view', $tenant);

        $days = $request->integer('days', 30);
        $profile = $this->insiderThreatService->getUserThreatProfile($user, $tenant, $days);

        return new JsonResponse($profile);
    }

    /**
     * Review insider threat log
     */
    public function reviewThreat(Request $request, InsiderThreatLog $threatLog): JsonResponse
    {
        $this->authorize('update', $threatLog->tenant);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $threatLog->markAsReviewed(
            reviewedById: $this->auth->id(),
            notes: $request->input('notes'),
        );

        return new JsonResponse([
            'message' => 'Threat log reviewed successfully',
        ]);
    }

    /**
     * Check if employee is in cool-down period
     */
    public function checkCoolDown(Tenant $tenant, User $employee): JsonResponse
    {
        $isInCoolDown = $this->deprovisionService->isInCoolDownPeriod($employee, $tenant);

        if (! $isInCoolDown) {
            return new JsonResponse([
                'in_cooldown' => false,
            ]);
        }

        $expiry = $this->deprovisionService->getCoolDownExpiry($employee, $tenant);

        return new JsonResponse([
            'in_cooldown' => true,
            'expires_at' => $expiry?->toIso8601String(),
            'remaining_days' => $expiry ? CarbonImmutable::now()->diffInDays($expiry) : null,
        ]);
    }
}
