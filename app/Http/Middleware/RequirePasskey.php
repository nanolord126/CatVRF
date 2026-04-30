<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\WebauthnCredential;

/**
 * Require Passkey Authentication Middleware
 * 
 * Enforces passkey authentication for sensitive operations.
 * Can be used for high-security actions like:
 * - Large payments
 * - Admin operations
 * - Data export
 * - Sensitive medical records access
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class RequirePasskey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user has any passkeys registered
        $hasPasskey = WebauthnCredential::where('user_id', $user->id)
            ->when(function_exists('tenant') && tenant(), fn ($q) => $q->where('tenant_id', tenant()->id))
            ->exists();

        if (!$hasPasskey) {
            return response()->json([
                'error' => 'Passkey required',
                'message' => 'This operation requires passkey authentication. Please register a passkey first.',
                'register_url' => '/api/v1/auth/passkey/register-options',
            ], 403);
        }

        // Check if the current session was authenticated with passkey
        // This requires tracking authentication method in the token/session
        $token = $request->bearerToken();
        
        if ($token) {
            // Check if token name indicates passkey authentication
            $tokenModel = $user->tokens()->where('token', hash('sha256', $token))->first();
            
            if (!$tokenModel || $tokenModel->name !== 'passkey-auth') {
                return response()->json([
                    'error' => 'Passkey required',
                    'message' => 'This operation requires passkey authentication. Please re-authenticate with your passkey.',
                    'login_url' => '/api/v1/auth/passkey/login-options',
                ], 403);
            }
        }

        return $next($request);
    }
}
