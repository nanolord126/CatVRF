<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use TwoFactorService;

use Psr\Log\LoggerInterface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\Verify2FARequest;
use App\Services\Auth\AuthService;
use App\Services\Security\BruteForceProtectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Services\Auth\TwoFactorService;

final class LoginController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly AuthService $auth,) {}

    /**
     * Login user
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();

        // Brute-force protection check
        $bruteForceService = BruteForceProtectionService::fromRequest($request);
        $checkResult = $bruteForceService->checkLoginAttempt();

        if (! $checkResult->allowed) {
            return new JsonResponse([
                'error' => 'brute_force_blocked',
                'message' => $checkResult->getErrorMessage(),
                'retry_after' => $checkResult->remainingSeconds,
                'correlation_id' => $correlationId,
            ], 429);
        }

        try {
            $result = $this->auth->login([
                'email' => $request->email,
                'password' => $request->password,
                'fingerprint' => $request->fingerprint,
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
                'location_country' => $request->location_country,
                'location_city' => $request->location_city,
                'correlation_id' => $correlationId,
            ]);

            if (isset($result['requires_2fa'])) {
                return new JsonResponse([
                    'requires_2fa' => true,
                    'user_id' => $result['user_id'],
                    'message' => '2FA verification required',
                    'correlation_id' => $correlationId,
                ]);
            }

            // Record successful login
            $bruteForceService->recordSuccess($result['user']);

            return new JsonResponse([
                'token' => $result['token'],
                'type' => $result['type'],
                'expires_at' => $result['expires_at'],
                'user' => [
                    'id' => $result['user']->id,
                    'uuid' => $result['user']->uuid,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role?->value,
                    'status' => $result['user']->status?->value,
                ],
                'device' => $result['device'] ? [
                    'id' => $result['device']->id,
                    'device_name' => $result['device']->device_name,
                    'is_trusted' => $result['device']->is_trusted,
                ] : null,
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            // Record failed login
            $bruteForceService->recordFailure();

            $this->log->channel('audit')->error('Login validation failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Throwable $e) {
            // Record failed login
            $bruteForceService->recordFailure();

            $this->log->channel('audit')->error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 401);
        }
    }

    /**
     * Verify 2FA and complete login
     * POST /api/v1/auth/login/2fa
     */
    public function verify2fa(Verify2FARequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();

        try {
            $user = User::findOrFail($request->user_id);

            // Check if user can authenticate (not locked/revoked)
            if (! $user->canAuthenticate()) {
                return new JsonResponse([
                    'error' => 'account_locked',
                    'message' => 'Account is locked or access has been revoked',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->twoFactorService /* TODO: inject via constructor DI */ /* TODO: inject via DI */->verify($user, $request->code);

            $result = $this->auth->completeLogin($user, [
                'fingerprint' => $request->fingerprint,
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
                'location_country' => $request->location_country,
                'location_city' => $request->location_city,
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->$this->logger->info('2FA login completed', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'token' => $result['token'],
                'type' => $result['type'],
                'expires_at' => $result['expires_at'],
                'user' => [
                    'id' => $result['user']->id,
                    'uuid' => $result['user']->uuid,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role?->value,
                    'status' => $result['user']->status?->value,
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('2FA login failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => '2FA verification failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 401);
        }
    }

    /**
     * Logout user
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $this->auth->logout($user);

        return new JsonResponse([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Logout from all devices
     * POST /api/v1/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $this->auth->logoutAll($user);

        return new JsonResponse([
            'message' => 'Logged out from all devices successfully.',
        ]);
    }

    /**
     * Refresh token
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Check if user can authenticate
        if (! $user->canAuthenticate()) {
            return new JsonResponse([
                'error' => 'account_locked',
                'message' => 'Account is locked or access has been revoked',
            ], 403);
        }

        $result = $this->auth->refreshToken($user);

        return new JsonResponse([
            'token' => $result['token'],
            'type' => $result['type'],
            'expires_at' => $result['expires_at'],
        ]);
    }

    /**
     * Get current user
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $activeTenant = $user->getActiveTenant();

        return new JsonResponse([
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->value,
                'status' => $user->status?->value,
                'email_verified_at' => $user->email_verified_at,
                'phone_verified_at' => $user->phone_verified_at,
                'two_factor_enabled' => $user->two_factor_enabled,
                'last_login_at' => $user->last_login_at,
                'is_locked' => $user->isLockedOut(),
                'is_revoked' => $user->isRevoked(),
            ],
            'tenant' => $activeTenant ? [
                'id' => $activeTenant->id,
                'name' => $activeTenant->name,
                'role' => $user->getRoleInTenant($activeTenant->id)?->value,
            ] : null,
            'correlation_id' => $correlationId,
        ]);
    }
}
