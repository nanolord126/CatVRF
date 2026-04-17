<?php declare(strict_types=1);

namespace App\Http\Middleware\Verticals;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class BeautyBiometricAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $biometricToken = $request->header('X-Biometric-Token') ?? $request->input('biometric_token');

        if (!$biometricToken) {
            return $next($request);
        }

        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required for biometric verification',
            ], 401);
        }

        if (!$this->validateBiometricToken($userId, $biometricToken)) {
            Log::channel('audit')->warning('beauty.biometric.auth.failed', [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid biometric token',
            ], 403);
        }

        Log::channel('audit')->info('beauty.biometric.auth.success', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        $request->attributes->set('biometric_authenticated', true);

        return $next($request);
    }

    private function validateBiometricToken(int $userId, string $token): bool
    {
        if (strlen($token) < 32) {
            return false;
        }

        $storedToken = $this->getStoredBiometricToken($userId);

        if (!$storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    private function getStoredBiometricToken(int $userId): ?string
    {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return null;
        }

        return $user->metadata['biometric_token'] ?? null;
    }
}
