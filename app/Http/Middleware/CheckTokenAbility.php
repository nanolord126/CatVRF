<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final readonly class CheckTokenAbility
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    /**
     * Handle an incoming request.
     *
     * @param  array<string>  $abilities
     */
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();

        if ($user === null) {
            $this->log->warning('CheckTokenAbility: No authenticated user', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'Authentication required',
            ], 401);
        }

        $currentToken = $user->currentAccessToken();

        if ($currentToken === null) {
            $this->log->warning('CheckTokenAbility: No current access token', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Check if token has any of the required abilities
        $hasAbility = false;
        foreach ($abilities as $ability) {
            if ($currentToken->can($ability)) {
                $hasAbility = true;
                break;
            }
        }

        if (!$hasAbility) {
            $this->log->warning('CheckTokenAbility: Insufficient abilities', [
                'user_id' => $user->id,
                'token_id' => $currentToken->id,
                'required_abilities' => $abilities,
                'token_abilities' => $currentToken->abilities,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'insufficient_permissions',
                'message' => 'Token does not have required abilities',
                'required' => $abilities,
            ], 403);
        }

        return $next($request);
    }
}
