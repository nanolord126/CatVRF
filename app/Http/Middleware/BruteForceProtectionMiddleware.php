<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\BruteForceProtectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final class BruteForceProtectionMiddleware
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for passkey authentication (already secure)
        if ($request->routeIs('passkey.*')) {
            return $next($request);
        }

        $protectionService = BruteForceProtectionService::fromRequest($request);
        $checkResult = $protectionService->checkLoginAttempt();

        if (!$checkResult->allowed) {
            $this->log->channel('security')->warning('Brute force blocked', [
                'ip_address' => $request->ip(),
                'email' => $request->input('email'),
                'reason' => $checkResult->reason,
                'remaining_seconds' => $checkResult->remainingSeconds,
                'user_id' => $checkResult->user?->id,
            ]);

            return response()->json([
                'message' => $checkResult->getErrorMessage(),
                'error' => 'brute_force_blocked',
                'retry_after' => $checkResult->remainingSeconds,
            ], 429);
        }

        // Store the service in request for later use in controller
        $request->attributes->set('brute_force_protection', $protectionService);

        return $next($request);
    }
}
