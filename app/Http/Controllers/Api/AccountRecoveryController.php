<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountRecoveryLog;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\Security\RecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;

final class AccountRecoveryController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly RecoveryService $recoveryService,
        private readonly ValidationFactory $validator,
    ) {}

    /**
     * Initiate account recovery
     */
    public function init(Request $request): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'method' => 'required|in:email,sms,backup_code,ai_face',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $recoveryLog = $this->recoveryService->initRecovery(
            $user,
            $request->method,
            $request->ip(),
            $request->userAgent(),
            $request->header('X-Device-Fingerprint') ?? $request->ip(),
        );

        return new JsonResponse([
            'success' => true,
            'recovery_log_id' => $recoveryLog->id,
            'method' => $recoveryLog->method,
            'risk_score' => $recoveryLog->risk_score,
            'requires_additional_verification' => $recoveryLog->isHighRisk(),
            'message' => 'Recovery initiated. Please check your email/SMS for verification code.',
        ]);
    }

    /**
     * Verify recovery step
     */
    public function verify(Request $request, string $recoveryLogId): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'verification_code' => 'required_without:face_image|string',
            'face_image' => 'required_without:verification_code|base64',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $recoveryLog = AccountRecoveryLog::where('id', $recoveryLogId)
            ->where('status', AccountRecoveryLog::STATUS_INITIATED)
            ->firstOrFail();

        $verified = $this->recoveryService->verifyStep(
            $recoveryLog,
            $request->verification_code,
            $request->face_image,
        );

        if (! $verified) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Verification failed. Please try again.',
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Verification successful. You can now complete recovery by registering a new passkey.',
            'next_step' => 'register_passkey',
        ]);
    }

    /**
     * Complete recovery with new passkey
     */
    public function complete(Request $request, string $recoveryLogId): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'credential_id' => 'required|string',
            'credential_public_key' => 'required|string',
            'counter' => 'required|integer',
            'transports' => 'array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $recoveryLog = AccountRecoveryLog::where('id', $recoveryLogId)
            ->where('status', AccountRecoveryLog::STATUS_VERIFIED)
            ->firstOrFail();

        $this->recoveryService->completeRecoveryWithNewPasskey(
            $recoveryLog,
            $request->credential_id,
            $request->credential_public_key,
            $request->counter,
            $request->transports ?? [],
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Recovery completed successfully. Your account has been secured with a new passkey.',
        ]);
    }

    /**
     * Get backup codes
     */
    public function getBackupCodes(Request $request): JsonResponse
    {
        $user = $this->auth->user();

        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->where('recovery_enabled', true)
            ->firstOrFail();

        // Return plain codes (only shown once)
        $backupCodes = $credential->backup_codes ?? [];

        return new JsonResponse([
            'success' => true,
            'backup_codes_remaining' => $credential->backup_codes_remaining,
            'backup_codes' => $backupCodes, // In production, these should be hashed
        ]);
    }

    /**
     * Regenerate backup codes
     */
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $user = $this->auth->user();

        $credential = WebauthnCredential::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->where('recovery_enabled', true)
            ->firstOrFail();

        $plainCodes = $this->recoveryService->regenerateBackupCodes($credential);

        return new JsonResponse([
            'success' => true,
            'message' => 'Backup codes regenerated. Save these codes securely.',
            'backup_codes' => $plainCodes,
        ]);
    }
}
