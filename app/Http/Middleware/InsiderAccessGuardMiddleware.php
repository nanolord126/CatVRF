<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\ClientDataProtectionService;
use App\Services\Security\CooldownService;
use App\Services\Security\InsiderThreatService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Insider Access Guard Middleware
 * 
 * Protects API endpoints from insider threats by:
 * - Validating staff access to client data
 * - Enforcing rate limits and result caps
 * - Detecting hunting patterns
 * - Blocking suspicious requests
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
final readonly class InsiderAccessGuardMiddleware
{
    public function __construct(
        private readonly ClientDataProtectionService $clientDataProtection,
        private readonly CooldownService $cooldownService,
        private readonly InsiderThreatService $insiderThreat,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (! $user) {
            return $next($request);
        }

        // Skip for customers accessing their own data
        if (! $user->role?->isBusiness() && ! $user->role?->isPlatformAdmin()) {
            return $next($request);
        }

        // Check if under cooldown
        if ($this->isUnderCooldown($user)) {
            return $this->denyAccess('Access denied: under cooldown period');
        }

        // Analyze request for insider threats
        $analysisResult = $this->analyzeRequest($request, $user);

        if ($analysisResult['blocked']) {
            return $this->denyAccess($analysisResult['reason'] ?? 'Access denied by security policy');
        }

        // Enforce result limits on response
        $response = $next($request);

        if ($response->isOk() && $this->isClientDataEndpoint($request)) {
            $response = $this->enforceResultLimits($response, $user, $request);
        }

        return $response;
    }

    /**
     * Check if user is under cooldown
     */
    private function isUnderCooldown(User $user): bool
    {
        $actionTypes = [
            \App\Enums\CooldownActionType::DATA_EXPORT,
            \App\Enums\CooldownActionType::HUNTING_DETECTED,
        ];

        foreach ($actionTypes as $actionType) {
            if ($this->cooldownService->isUnderCooldown($user, $actionType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze request for insider threat patterns
     */
    private function analyzeRequest(Request $request, User $user): array
    {
        $result = [
            'blocked' => false,
            'reason' => null,
            'anomaly_score' => 0.0,
        ];

        // Check rate limit
        if (! $this->checkRateLimit($user, $request)) {
            $result['blocked'] = true;
            $result['reason'] = 'Rate limit exceeded';
            return $result;
        }

        // Check for hunting patterns in query parameters
        if ($this->detectHuntingPattern($request)) {
            $result['anomaly_score'] += 0.3;
        }

        // Check for mass export attempt
        if ($this->detectMassExportAttempt($request)) {
            $result['anomaly_score'] += 0.4;
        }

        // Check for cross-tenant access attempt
        if ($this->detectCrossTenantAccess($request, $user)) {
            $result['anomaly_score'] += 0.5;
        }

        // Block if anomaly score exceeds threshold
        $threshold = config('insider-protection.behavioral.anomaly_score_threshold', 0.85);
        if ($result['anomaly_score'] >= $threshold) {
            $result['blocked'] = true;
            $result['reason'] = 'Suspicious activity detected';
        }

        return $result;
    }

    /**
     * Check rate limit for user
     */
    private function checkRateLimit(User $user, Request $request): bool
    {
        $role = $user->role?->value ?? 'customer';
        $maxRequests = config("insider-protection.data_access.rate_limit_per_minute.{$role}", 10);

        $cacheKey = "rate_limit:api:{$user->id}:" . now()->format('Y-m-d-H:i');
        $current = cache()->get($cacheKey, 0);

        if ($current >= $maxRequests) {
            return false;
        }

        cache()->put($cacheKey, $current + 1, 60);

        return true;
    }

    /**
     * Detect hunting patterns in request
     */
    private function detectHuntingPattern(Request $request): bool
    {
        $query = $request->query();
        
        // Check for email/phone pattern searches
        $huntingPatterns = ['email', 'phone', 'contact', 'search'];
        
        foreach ($query as $key => $value) {
            foreach ($huntingPatterns as $pattern) {
                if (str_contains(strtolower($key), $pattern)) {
                    // Check if it's a pattern match (wildcards)
                    if (str_contains($value, '%') || str_contains($value, '*')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Detect mass export attempt
     */
    private function detectMassExportAttempt(Request $request): bool
    {
        // Check for export endpoints
        if (str_contains($request->path(), 'export') || str_contains($request->path(), 'download')) {
            // Check for large limit parameter
            $limit = $request->input('limit', $request->input('per_page', 50));
            $threshold = config('insider-protection.behavioral.mass_operation_threshold', 100);

            if ($limit > $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect cross-tenant access attempt
     */
    private function detectCrossTenantAccess(Request $request, User $user): bool
    {
        // Super-admins can access all tenants
        if ($user->role?->isPlatformAdmin()) {
            return false;
        }

        // Check if request includes tenant_id parameter
        $requestedTenantId = $request->input('tenant_id');
        $userTenantId = $user->tenant_id;

        if ($requestedTenantId && $requestedTenantId != $userTenantId) {
            return true;
        }

        return false;
    }

    /**
     * Check if request is for client data endpoint
     */
    private function isClientDataEndpoint(Request $request): bool
    {
        $clientDataPaths = [
            'users',
            'customers',
            'clients',
            'orders',
        ];

        $path = $request->path();

        foreach ($clientDataPaths as $protectedPath) {
            if (str_contains($path, $protectedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enforce result limits on response
     */
    private function enforceResultLimits(Response $response, User $user, Request $request): Response
    {
        $role = $user->role?->value ?? 'customer';
        $maxRecords = config("insider-protection.data_access.max_records_per_request.{$role}", 50);

        // Only apply to JSON responses
        if (! str_contains($response->headers->get('Content-Type'), 'application/json')) {
            return $response;
        }

        $data = json_decode($response->getContent(), true);

        if (! is_array($data)) {
            return $response;
        }

        // Handle paginated responses (Laravel standard format)
        if (isset($data['data']) && is_array($data['data'])) {
            if (count($data['data']) > $maxRecords) {
                $data['data'] = array_slice($data['data'], 0, $maxRecords);
                $data['meta']['truncated'] = true;
                $data['meta']['max_records'] = $maxRecords;
                $response->setContent(json_encode($data));
            }
        }
        // Handle simple array responses
        elseif (is_array($data) && count($data) > $maxRecords) {
            $data = array_slice($data, 0, $maxRecords);
            $response->setContent(json_encode($data));
        }

        return $response;
    }

    /**
     * Deny access with appropriate response
     */
    private function denyAccess(string $message): Response
    {
        return response()->json([
            'error' => 'access_denied',
            'message' => $message,
        ], 403);
    }
}
