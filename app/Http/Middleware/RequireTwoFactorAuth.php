<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require Two-Factor Authentication Middleware
 * 
 * Middleware для требования двухфакторной аутентификации
 * для критических операций (compliance)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class RequireTwoFactorAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        // Проверка включена ли 2FA для пользователя
        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication is required for this operation',
                'requires_2fa_setup' => true,
            ], 403);
        }

        // Проверка подтверждения 2FA в текущей сессии
        if (!$request->session()->get('2fa_confirmed', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication confirmation required',
                'requires_2fa_confirmation' => true,
            ], 403);
        }

        return $next($request);
    }
}
