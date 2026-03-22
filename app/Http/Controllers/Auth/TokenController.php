<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * TokenController — управление Personal Access Token для API.
 */
final class TokenController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}
    /**
     * Создать новый token
     */
    public function create(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid()->toString();
        $this->fraudControlService->check(0, 'token_create', 0, $request->ip(), null, $correlationId);

        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $user = \App\Models\User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                Log::channel('audit')->warning('Invalid token creation credentials', [
                    'email' => $validated['email'],
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'error' => 'Invalid credentials',
                    'correlation_id' => $correlationId,
                ], 401);
            }

            return DB::transaction(function () use ($user, $correlationId) {
                $token = $user->createToken(
                    name: 'API Token',
                    abilities: ['*'],
                    expiresAt: now()->addDays(365)
                );

                Log::channel('audit')->info('Token created', [
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'token' => $token->plainTextToken,
                    'type' => 'Bearer',
                    'expires_in' => 86400,
                    'correlation_id' => $correlationId,
                ], 201);
            });

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Token creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Token creation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Обновить token
     */
    public function refresh(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid()->toString();

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 401);
            }

            return DB::transaction(function () use ($user, $correlationId) {
                $user->tokens()->delete();
                $token = $user->createToken('API Token Refreshed');

                Log::channel('audit')->info('Token refreshed', [
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'token' => $token->plainTextToken,
                    'type' => 'Bearer',
                    'correlation_id' => $correlationId,
                ], 200);
            });

        } catch (\Throwable $e) {
            Log::channel('audit')->error('Token refresh failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Token refresh failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
