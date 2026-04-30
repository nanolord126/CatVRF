<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Security\RecoveryService;
use App\Services\Security\UserDeviceService;
use App\Services\Security\AuditService;
use App\Models\AccountRecoveryLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Account Recovery Controller
 *
 * Handles account recovery with risk-based verification.
 * Supports multiple recovery methods: email, SMS, backup codes, AI face verification.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class RecoveryController extends Controller
{
    public function __construct(
        private readonly RecoveryService $recoveryService,
        private readonly UserDeviceService $userDeviceService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Initiate account recovery
     *
     * POST /api/v1/auth/recovery/init
     */
    public function init(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'method' => 'required|in:email,sms,backup_code,ai_face',
        ]);

        // Find user by email
        $user = User::where('email', $request->input('email'))->first();

        // Don't leak user existence - return success even if user not found
        if (! $user) {
            $this->auditService->logRecoveryEvent(
                'init_attempt_user_not_found',
                0,
                null,
                $request->input('method'),
                0.0,
                ['email' => $request->input('email')]
            );

            return new JsonResponse([
                'message' => 'If the email exists, recovery instructions will be sent.',
            ], 200);
        }

        // Generate device fingerprint
        $deviceFingerprint = $this->userDeviceService->generateFingerprint([
            'user_agent' => $request->userAgent(),
            'screen_resolution' => $request->header('Screen-Resolution'),
            'timezone' => $request->header('Timezone'),
            'language' => $request->header('Accept-Language'),
        ]);

        try {
            $recoveryLog = $this->recoveryService->initRecovery(
                user: $user,
                method: $request->input('method'),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                deviceFingerprint: $deviceFingerprint,
            );

            return new JsonResponse([
                'message' => 'Recovery initiated',
                'recovery_id' => $recoveryLog->id,
                'method' => $recoveryLog->method,
                'risk_score' => $recoveryLog->risk_score,
                'expires_at' => $recoveryLog->initiated_at?->addMinutes(10)?->toIso8601String(),
            ], 200);
        } catch (\RuntimeException $e) {
            $this->auditService->logRecoveryEvent(
                'init_failed',
                $user->id,
                $user->tenant_id,
                $request->input('method'),
                0.0,
                ['error' => $e->getMessage()]
            );

            return new JsonResponse([
                'error' => $e->getMessage(),
                'error_code' => 'RECOVERY_INIT_FAILED',
            ], 400);
        }
    }

    /**
     * Verify recovery step
     *
     * POST /api/v1/auth/recovery/{recoveryId}/verify
     */
    public function verify(Request $request, string $recoveryId): JsonResponse
    {
        $request->validate([
            'verification_code' => 'required_without:face_image|string',
            'face_image' => 'required_without:verification_code|string',
        ]);

        $recoveryLog = AccountRecoveryLog::where('id', $recoveryId)->first();

        if (! $recoveryLog) {
            return new JsonResponse([
                'error' => 'Recovery not found',
                'error_code' => 'RECOVERY_NOT_FOUND',
            ], 404);
        }

        try {
            $verified = $this->recoveryService->verifyStep(
                recoveryLog: $recoveryLog,
                verificationCode: $request->input('verification_code'),
                faceImageBase64: $request->input('face_image'),
            );

            if (! $verified) {
                return new JsonResponse([
                    'error' => 'Verification failed',
                    'error_code' => 'VERIFICATION_FAILED',
                ], 400);
            }

            return new JsonResponse([
                'message' => 'Verification successful',
                'status' => $recoveryLog->status,
                'next_step' => 'create_new_passkey',
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'error_code' => 'VERIFICATION_ERROR',
            ], 400);
        }
    }

    /**
     * Complete recovery with new passkey
     *
     * POST /api/v1/auth/recovery/{recoveryId}/complete
     */
    public function complete(Request $request, string $recoveryId): JsonResponse
    {
        $request->validate([
            'credential_id' => 'required|string',
            'credential_public_key' => 'required|string',
            'counter' => 'required|integer',
            'transports' => 'sometimes|array',
        ]);

        $recoveryLog = AccountRecoveryLog::where('id', $recoveryId)->first();

        if (! $recoveryLog) {
            return new JsonResponse([
                'error' => 'Recovery not found',
                'error_code' => 'RECOVERY_NOT_FOUND',
            ], 404);
        }

        try {
            $this->recoveryService->completeRecoveryWithNewPasskey(
                recoveryLog: $recoveryLog,
                credentialId: $request->input('credential_id'),
                credentialPublicKey: $request->input('credential_public_key'),
                counter: (int) $request->input('counter'),
                transports: $request->input('transports', []),
            );

            return new JsonResponse([
                'message' => 'Recovery completed successfully',
                'status' => 'completed',
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'error_code' => 'RECOVERY_COMPLETE_FAILED',
            ], 400);
        }
    }

    /**
     * Get backup codes for a credential
     *
     * GET /api/v1/auth/recovery/backup-codes
     */
    public function getBackupCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $credential = $user->webauthnCredentials()
            ->where('tenant_id', $user->tenant_id)
            ->where('recovery_enabled', true)
            ->first();

        if (! $credential) {
            return new JsonResponse([
                'error' => 'No credential with recovery enabled',
                'error_code' => 'NO_RECOVERY_CREDENTIAL',
            ], 404);
        }

        return new JsonResponse([
            'backup_codes_remaining' => $credential->backup_codes_remaining ?? 0,
            'last_backup_code_used_at' => $credential->last_backup_code_used_at?->toIso8601String(),
        ]);
    }

    /**
     * Regenerate backup codes
     *
     * POST /api/v1/auth/recovery/backup-codes/regenerate
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $request->validate([
            'credential_id' => 'required|integer',
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $credential = $user->webauthnCredentials()
            ->where('id', $request->input('credential_id'))
            ->where('tenant_id', $user->tenant_id)
            ->first();

        if (! $credential) {
            return new JsonResponse([
                'error' => 'Credential not found',
                'error_code' => 'CREDENTIAL_NOT_FOUND',
            ], 404);
        }

        try {
            $plainCodes = $this->recoveryService->regenerateBackupCodes($credential);

            return new JsonResponse([
                'message' => 'Backup codes regenerated',
                'backup_codes' => $plainCodes,
                'warning' => 'Save these codes securely. They will not be shown again.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'error_code' => 'BACKUP_CODES_REGENERATION_FAILED',
            ], 400);
        }
    }

    /**
     * Get recovery history for user
     *
     * GET /api/v1/auth/recovery/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $history = AccountRecoveryLog::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('initiated_at', 'desc')
            ->limit(20)
            ->get();

        return new JsonResponse([
            'history' => $history->map(fn ($log) => [
                'id' => $log->id,
                'method' => $log->method,
                'status' => $log->status,
                'risk_score' => $log->risk_score,
                'initiated_at' => $log->initiated_at?->toIso8601String(),
                'completed_at' => $log->completed_at?->toIso8601String(),
                'failure_reason' => $log->failure_reason,
            ]),
        ]);
    }
}
