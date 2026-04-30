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
 * Check Cooldown Middleware
 * 
 * Middleware to check if user/tenant is under cooldown for financial operations.
 * Should be applied to all financial routes (withdrawals, transfers, etc.).
 */
final class CheckCooldownMiddleware
{
    public function __construct(
        private readonly CooldownService $cooldownService
    ) {}

    /**
     * Handle an incoming request.
     * 
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string|null  $actionType  Optional specific action type to check
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $actionType = null): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        // Determine action type from route or parameter
        $actionTypeEnum = $this->determineActionType($request, $actionType);

        if ($actionTypeEnum === null) {
            return $next($request);
        }

        // Check if under cooldown
        if ($this->cooldownService->isUnderCooldown($user, $actionTypeEnum)) {
            $remainingTime = $this->cooldownService->getRemainingTimeForHumans(
                $user,
                $actionTypeEnum
            );

            return response()->json([
                'error' => 'cooldown_active',
                'message' => 'Период охлаждения активен. Операция временно недоступна.',
                'action_type' => $actionTypeEnum->value,
                'remaining_time' => $remainingTime,
                'remaining_seconds' => $this->cooldownService->getRemainingTime(
                    $user,
                    $actionTypeEnum
                ),
            ], 403);
        }

        return $next($request);
    }

    /**
     * Determine action type from request or parameter
     */
    private function determineActionType(Request $request, ?string $actionTypeParam): ?CooldownActionType
    {
        // If action type is explicitly provided in middleware parameter
        if ($actionTypeParam !== null) {
            try {
                return CooldownActionType::from($actionTypeParam);
            } catch (\ValueError) {
                return null;
            }
        }

        // Determine from route name or path
        $route = $request->route();
        $routeName = $route?->getName() ?? '';
        $path = $request->path();

        // Map routes to action types
        if (str_contains($path, 'withdraw') || str_contains($routeName, 'withdraw')) {
            return CooldownActionType::WITHDRAWAL;
        }

        if (str_contains($path, 'transfer') || str_contains($routeName, 'transfer')) {
            return CooldownActionType::TRANSFER;
        }

        if (str_contains($path, 'payout') || str_contains($routeName, 'payout')) {
            return CooldownActionType::WITHDRAWAL;
        }

        return null;
    }
}
