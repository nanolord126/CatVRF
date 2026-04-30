<?php

declare(strict_types=1);

namespace App\Traits\Guards;

use App\Enums\CooldownActionType;
use App\Services\Security\CooldownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * High Risk Action Guard Trait
 *
 * Trait for controllers to manually check and block high-risk actions
 * when user/tenant is under cooldown due to VPN/Proxy detection.
 *
 * Usage in controller:
 * ```php
 * use App\Traits\Guards\HighRiskActionGuard;
 *
 * class WalletController extends Controller
 * {
 *     use HighRiskActionGuard;
 *
 *     public function withdraw(Request $request)
 *     {
 *         if ($this->isFinancialOperationsBlocked()) {
 *             return $this->getFinancialOperationsBlockedResponse();
 *         }
 *
 *         // Proceed with withdrawal logic
 *     }
 * }
 * ```
 *
 * Production 2026 CANON:
 * - Provides convenient methods for checking cooldown status
 * - Returns consistent error responses
 * - Can be used in addition to middleware for fine-grained control
 */
trait HighRiskActionGuard
{
    /**
     * Check if financial operations are blocked for current user
     *
     * @return bool
     */
    protected function isFinancialOperationsBlocked(): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return app(CooldownService::class)->isUnderCooldown(
            $user,
            CooldownActionType::FINANCIAL_OPERATIONS
        );
    }

    /**
     * Check if critical changes are blocked for current user
     *
     * @return bool
     */
    protected function isCriticalChangesBlocked(): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return app(CooldownService::class)->isUnderCooldown(
            $user,
            CooldownActionType::CRITICAL_CHANGES
        );
    }

    /**
     * Check if specific action type is blocked for current user
     *
     * @param  CooldownActionType  $actionType
     * @return bool
     */
    protected function isActionBlocked(CooldownActionType $actionType): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }

        return app(CooldownService::class)->isUnderCooldown($user, $actionType);
    }

    /**
     * Get JSON response for blocked financial operations
     *
     * @return JsonResponse
     */
    protected function getFinancialOperationsBlockedResponse(): JsonResponse
    {
        return $this->getBlockedResponse(CooldownActionType::FINANCIAL_OPERATIONS);
    }

    /**
     * Get JSON response for blocked critical changes
     *
     * @return JsonResponse
     */
    protected function getCriticalChangesBlockedResponse(): JsonResponse
    {
        return $this->getBlockedResponse(CooldownActionType::CRITICAL_CHANGES);
    }

    /**
     * Get JSON response for blocked action
     *
     * @param  CooldownActionType  $actionType
     * @return JsonResponse
     */
    protected function getBlockedResponse(CooldownActionType $actionType): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        $cooldownService = app(CooldownService::class);
        $remainingTime = $cooldownService->getRemainingTimeForHumans($user, $actionType);
        $remainingSeconds = $cooldownService->getRemainingTime($user, $actionType);

        $message = match ($actionType) {
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

        $verificationRequirements = $this->getVerificationRequirements($actionType);

        // Log blocked attempt
        \Log::channel('security')->warning('High-risk action blocked by cooldown (trait)', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action_type' => $actionType->value,
            'remaining_seconds' => $remainingSeconds,
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
     * Get remaining time for financial operations
     *
     * @return string
     */
    protected function getFinancialOperationsRemainingTime(): string
    {
        $user = Auth::user();
        if ($user === null) {
            return '';
        }

        return app(CooldownService::class)->getRemainingTimeForHumans(
            $user,
            CooldownActionType::FINANCIAL_OPERATIONS
        );
    }

    /**
     * Get remaining time for critical changes
     *
     * @return string
     */
    protected function getCriticalChangesRemainingTime(): string
    {
        $user = Auth::user();
        if ($user === null) {
            return '';
        }

        return app(CooldownService::class)->getRemainingTimeForHumans(
            $user,
            CooldownActionType::CRITICAL_CHANGES
        );
    }

    /**
     * Get verification requirements for bypassing the cooldown
     *
     * @param  CooldownActionType  $actionType
     * @return array
     */
    protected function getVerificationRequirements(CooldownActionType $actionType): array
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

    /**
     * Get all active cooldowns for current user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getActiveCooldowns()
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return app(CooldownService::class)->getActiveCooldownsForUser($user);
    }
}
