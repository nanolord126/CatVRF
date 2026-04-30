<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\CooldownActionType;
use App\Services\Security\CooldownService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * High Risk Action Guard Middleware
 *
 * Protects high-risk actions (financial operations, critical changes) from being performed
 * when user/tenant is under cooldown due to VPN/Proxy detection.
 *
 * This middleware should be applied to:
 * - Withdrawal endpoints
 * - Transfer endpoints
 * - Bank details change endpoints
 * - Contact info change endpoints
 * - KYB approval endpoints
 * - Staff invitation endpoints (manager+ roles)
 *
 * Production 2026 CANON:
 * - Checks both user-level and tenant-level cooldowns
 * - Returns clear error messages with remaining time
 * - Provides actionable information for bypassing (verification requirements)
 * - Logs all blocked attempts for audit
 */
final class HighRiskActionGuard
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string|null  $actionType  Optional specific action type to check (financial_operations or critical_changes)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $actionType = null): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        // Determine which action types to check based on parameter or route
        $actionTypes = $this->determineActionTypes($request, $actionType);

        if (empty($actionTypes)) {
            return $next($request);
        }

        // Check each action type
        foreach ($actionTypes as $actionTypeEnum) {
            if ($this->cooldownService->isUnderCooldown($user, $actionTypeEnum)) {
                return $this->handleBlockedAction($request, $user, $actionTypeEnum);
            }
        }

        return $next($request);
    }

    /**
     * Determine which action types to check based on request or parameter
     *
     * @param  Request  $request
     * @param  string|null  $actionTypeParam
     * @return array<CooldownActionType>
     */
    private function determineActionTypes(Request $request, ?string $actionTypeParam): array
    {
        // If action type is explicitly provided in middleware parameter
        if ($actionTypeParam !== null) {
            try {
                return [CooldownActionType::from($actionTypeParam)];
            } catch (\ValueError) {
                return [];
            }
        }

        // Determine from route name or path
        $route = $request->route();
        $routeName = $route?->getName() ?? '';
        $path = $request->path();

        $actionTypes = [];

        // Financial operations mapping
        if (str_contains($path, 'withdraw') || str_contains($routeName, 'withdraw') ||
            str_contains($path, 'transfer') || str_contains($routeName, 'transfer') ||
            str_contains($path, 'payout') || str_contains($routeName, 'payout') ||
            str_contains($path, 'bank') && str_contains($path, 'change') ||
            str_contains($path, 'wallet') && (str_contains($path, 'withdraw') || str_contains($path, 'transfer'))) {
            $actionTypes[] = CooldownActionType::FINANCIAL_OPERATIONS;
        }

        // Critical changes mapping
        if (str_contains($path, 'contact') && (str_contains($path, 'change') || str_contains($path, 'update')) ||
            str_contains($path, 'email') && str_contains($path, 'change') ||
            str_contains($path, 'phone') && str_contains($path, 'change') ||
            str_contains($path, 'kyb') && (str_contains($path, 'approve') || str_contains($path, 'verify')) ||
            str_contains($path, 'staff') && str_contains($path, 'invite') ||
            str_contains($path, 'role') && str_contains($path, 'change')) {
            $actionTypes[] = CooldownActionType::CRITICAL_CHANGES;
        }

        return $actionTypes;
    }

    /**
     * Handle blocked action response
     *
     * @param  Request  $request
     * @param  \App\Models\User  $user
     * @param  CooldownActionType  $actionType
     * @return Response
     */
    private function handleBlockedAction(Request $request, \App\Models\User $user, CooldownActionType $actionType): Response
    {
        $remainingTime = $this->cooldownService->getRemainingTimeForHumans($user, $actionType);
        $remainingSeconds = $this->cooldownService->getRemainingTime($user, $actionType);

        $message = $this->getBlockedMessage($actionType, $remainingTime);
        $verificationRequirements = $this->getVerificationRequirements($actionType);

        // Log blocked attempt
        \Log::channel('security')->warning('High-risk action blocked by cooldown', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action_type' => $actionType->value,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'remaining_seconds' => $remainingSeconds,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'error' => 'high_risk_action_blocked',
            'message' => $message,
            'action_type' => $actionType->value,
            'remaining_time' => $remainingTime,
            'remaining_seconds' => $remainingSeconds,
            'verification_requirements' => $verificationRequirements,
            'support_url' => config('vpn-detection.support_url', '/support/security'),
        ], 403);
    }

    /**
     * Get user-friendly blocked message
     *
     * @param  CooldownActionType  $actionType
     * @param  string  $remainingTime
     * @return string
     */
    private function getBlockedMessage(CooldownActionType $actionType, string $remainingTime): string
    {
        return match ($actionType) {
            CooldownActionType::FINANCIAL_OPERATIONS => 
                sprintf(
                    'Финансовые операции временно заблокированы из-за обнаружения VPN/Proxy. Осталось: %s. Пройдите повторную верификацию для снятия ограничения.',
                    $remainingTime
                ),
            CooldownActionType::CRITICAL_CHANGES => 
                sprintf(
                    'Критические изменения временно заблокированы из-за обнаружения VPN/Proxy. Осталось: %s. Пройдите повторную верификацию для снятия ограничения.',
                    $remainingTime
                ),
            default => 
                sprintf(
                    'Операция временно недоступна. Период охлаждения: %s.',
                    $remainingTime
                ),
        };
    }

    /**
     * Get verification requirements for bypassing the cooldown
     *
     * @param  CooldownActionType  $actionType
     * @return array
     */
    private function getVerificationRequirements(CooldownActionType $actionType): array
    {
        $requirements = [
            'passkey_verification' => true,
            'liveness_check' => false,
            'additional_documentation' => false,
        ];

        // High risk actions require liveness check
        if ($actionType === CooldownActionType::FINANCIAL_OPERATIONS) {
            $requirements['liveness_check'] = true;
            $requirements['additional_documentation'] = false;
        }

        // Critical changes may require additional documentation
        if ($actionType === CooldownActionType::CRITICAL_CHANGES) {
            $requirements['liveness_check'] = true;
            $requirements['additional_documentation'] = true;
        }

        return $requirements;
    }
}
