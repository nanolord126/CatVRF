<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Security\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final class RequirePasskeyOrHighVerification
{
    public function __construct(
        private readonly LogManager $log,
        private readonly AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Check if user has passkey credentials
        $hasPasskey = $user->webauthnCredentials()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_compromised', false)
            ->exists();

        // Check if request has passkey verification header
        $passkeyVerified = $request->header('X-Passkey-Verified') === 'true';

        // Check if request has high verification (AI face, etc.)
        $highVerification = $request->header('X-High-Verification') === 'true';

        // Allow if has passkey and verified
        if ($hasPasskey && $passkeyVerified) {
            return $next($request);
        }

        // Allow if high verification (even without passkey)
        if ($highVerification) {
            return $next($request);
        }

        // Deny if neither
        $this->auditService->logEvent('access_denied_insufficient_verification', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'has_passkey' => $hasPasskey,
            'passkey_verified' => $passkeyVerified,
            'high_verification' => $highVerification,
            'path' => $request->path(),
        ], 'security');

        $this->log->warning('Access denied - insufficient verification', [
            'user_id' => $user->id,
            'has_passkey' => $hasPasskey,
            'path' => $request->path(),
        ]);

        return response()->json([
            'error' => 'This action requires passkey or high verification',
            'requires' => $hasPasskey ? 'passkey_verification' : 'high_verification',
        ], 403);
    }
}
