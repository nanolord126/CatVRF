<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use Psr\Log\LoggerInterface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\Enable2FARequest;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TwoFactorController extends Controller
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly TwoFactorService $twoFactor,) {}

    /**
     * Enable 2FA
     * POST /api/v1/auth/2fa/enable
     */
    public function enable(Enable2FARequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        if ($user->two_factor_enabled) {
            return new JsonResponse([
                'error' => '2FA is already enabled',
                'correlation_id' => $correlationId,
            ], 400);
        }

        $result = $this->twoFactor->enable($user);

        $this->log->channel('audit')->$this->logger->info('2FA setup initiated', [
            'user_id' => $user->id,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'secret' => $result['secret'],
            'qr_code_url' => $result['qr_code_url'],
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Confirm 2FA setup
     * POST /api/v1/auth/2fa/confirm
     */
    public function confirm(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        try {
            $this->twoFactor->confirm($user, $validated['code']);

            $this->log->channel('audit')->$this->logger->info('2FA enabled', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => '2FA enabled successfully',
                'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            $this->log->channel('audit')->error('2FA confirmation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Disable 2FA
     * POST /api/v1/auth/2fa/disable
     */
    public function disable(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        try {
            $this->twoFactor->disable($user, $validated['code']);

            $this->log->channel('audit')->$this->logger->info('2FA disabled', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => '2FA disabled successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            $this->log->channel('audit')->error('2FA disable failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Send email code (fallback)
     * POST /api/v1/auth/2fa/send-email-code
     */
    public function sendEmailCode(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        $code = $this->twoFactor->sendEmailCode($user);

        $this->log->channel('audit')->$this->logger->info('2FA email code sent', [
            'user_id' => $user->id,
            'correlation_id' => $correlationId,
        ]);

        return new JsonResponse([
            'message' => 'Verification code sent to email',
            'code' => app()->environment('local', 'testing') ? $code : null,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Verify email code
     * POST /api/v1/auth/2fa/verify-email-code
     */
    public function verifyEmailCode(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        try {
            $this->twoFactor->verifyEmailCode($user, $validated['code']);

            $this->log->channel('audit')->$this->logger->info('2FA email code verified', [
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => 'Email code verified successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (ValidationException $e) {
            $this->log->channel('audit')->error('2FA email code verification failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
