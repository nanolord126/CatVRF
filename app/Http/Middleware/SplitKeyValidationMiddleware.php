<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTO\SplitKey\ValidateSplitKeyDTO;
use App\Services\Security\DeviceBinding\DeviceBindingService;
use App\Services\Security\SplitKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Psr\Log\LoggerInterface;

/**
 * Split Key Validation Middleware
 *
 * Validates split key for sensitive API and Filament operations.
 * Requires challenge-response signature verification.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class SplitKeyValidationMiddleware
{
    public function __construct(
        private readonly SplitKeyService $splitKeyService,
        private readonly DeviceBindingService $deviceBinding,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'error' => 'Unauthenticated',
            ], 401);
        }

        // Skip split key validation for non-sensitive routes
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Check if user has active split key
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : null;
        
        if (! $this->splitKeyService->hasActiveKey($user->id, $tenantId)) {
            $this->logger->warning('No active split key found for sensitive operation', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Split key required for this operation',
                'requires_split_key' => true,
            ], 403);
        }

        // Validate split key signature
        $challenge = $request->header('X-Split-Key-Challenge');
        $signature = $request->header('X-Split-Key-Signature');

        if (! $challenge || ! $signature) {
            $this->logger->warning('Missing split key challenge/signature headers', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Split key challenge and signature required',
                'requires_split_key' => true,
            ], 403);
        }

        $validateDto = new ValidateSplitKeyDTO(
            userId: $user->id,
            tenantId: $tenantId,
            challenge: $challenge,
            signature: $signature,
            ipAddress: $request->ip(),
            correlationId: $request->header('X-Correlation-ID'),
        );

        $result = $this->splitKeyService->validateAndUse($validateDto);

        if (! $result['valid']) {
            $this->logger->warning('Split key validation failed', [
                'user_id' => $user->id,
                'route' => $request->route()?->getName(),
                'error' => $result['error'] ?? 'Unknown error',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => $result['error'] ?? 'Split key validation failed',
                'requires_split_key' => true,
            ], 403);
        }

        // Add split key info to request for downstream use
        $request->attributes->set('split_key', $result['split_key']);

        return $next($request);
    }

    /**
     * Determine if validation should be skipped for this request
     *
     * @param  Request  $request
     * @return bool
     */
    private function shouldSkipValidation(Request $request): bool
    {
        // Skip for public routes
        $publicRoutes = [
            'api/*',
            'login',
            'register',
            'password/*',
            'sanctum/csrf-cookie',
        ];

        foreach ($publicRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Skip for health checks
        if ($request->is('health') || $request->is('api/health')) {
            return true;
        }

        // Skip for routes that explicitly opt out
        if ($request->hasHeader('X-Skip-Split-Key-Validation')) {
            return true;
        }

        // Require split key for financial operations
        $financialRoutes = [
            'api/payments/*',
            'api/withdrawals/*',
            'api/transfers/*',
            'api/bank-accounts/*',
            'filament/*/resources/*/edit',
            'filament/*/resources/*/create',
        ];

        foreach ($financialRoutes as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        // Default: require for POST/PUT/DELETE on API routes
        if ($request->is('api/*') && in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return false;
        }

        // Default: skip for GET requests
        return true;
    }
}
