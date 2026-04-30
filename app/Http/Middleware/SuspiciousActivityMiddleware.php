<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\AccountProtectionService;
use App\Services\Security\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final class SuspiciousActivityMiddleware
{
    public function __construct(
        private readonly AccountProtectionService $accountProtectionService,
        private readonly AuditService $auditService,
        private readonly LogManager $log,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        // Get device fingerprint from header
        $deviceFingerprint = $request->header('X-Device-Fingerprint') ?? $request->ip();
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

        // Detect anomalies
        $anomalyResult = $this->accountProtectionService->detectAnomaly(
            $user,
            $ipAddress,
            $userAgent,
            $deviceFingerprint
        );

        // Store anomaly result in request for later use
        $request->attributes->set('anomaly_detection', $anomalyResult);

        // Block if high risk
        if ($anomalyResult['should_block']) {
            $this->accountProtectionService->lockAccount($user, 'suspicious_activity_detected', [
                'anomalies' => $anomalyResult['anomalies'],
                'risk_score' => $anomalyResult['risk_score'],
                'ip_address' => $ipAddress,
                'device_fingerprint' => $deviceFingerprint,
            ]);

            $this->auditService->logEvent('request_blocked_suspicious', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'anomalies' => $anomalyResult['anomalies'],
                'risk_score' => $anomalyResult['risk_score'],
                'ip_address' => $ipAddress,
                'path' => $request->path(),
            ], 'security');

            $this->log->warning('Request blocked due to suspicious activity', [
                'user_id' => $user->id,
                'risk_score' => $anomalyResult['risk_score'],
                'anomalies' => $anomalyResult['anomalies'],
            ]);

            return response()->json([
                'error' => 'Request blocked due to suspicious activity',
                'requires_verification' => true,
            ], 403);
        }

        // Check if account is locked
        if ($this->accountProtectionService->isAccountLocked($user)) {
            $this->auditService->logEvent('request_blocked_locked_account', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'ip_address' => $ipAddress,
                'path' => $request->path(),
            ], 'security');

            return response()->json([
                'error' => 'Account is locked. Please contact support.',
            ], 403);
        }

        // Continue with request
        $response = $next($request);

        // Add security headers
        $response->headers->set('X-Security-Risk-Score', (string) $anomalyResult['risk_score']);
        $response->headers->set('X-Requires-Additional-Verification', $anomalyResult['requires_additional_verification'] ? 'true' : 'false');

        return $response;
    }
}
