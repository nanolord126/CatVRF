<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\AccountProtectionService;
use App\Services\Security\AuditService;
use App\Services\Security\DeepfakeDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;

final class SecurityController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly AccountProtectionService $accountProtectionService,
        private readonly DeepfakeDetectionService $deepfakeDetectionService,
        private readonly AuditService $auditService,
        private readonly ValidationFactory $validator,
    ) {}

    /**
     * Get security status for current user
     */
    public function status(Request $request): JsonResponse
    {
        $user = $this->auth->user();

        $isAccountLocked = $this->accountProtectionService->isAccountLocked($user);
        $fraudScore = $this->accountProtectionService->getUserFraudScore($user->id);

        $passkeyCount = $user->webauthnCredentials()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_compromised', false)
            ->count();

        $deviceCount = $user->devices()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_revoked', false)
            ->count();

        return new JsonResponse([
            'account_locked' => $isAccountLocked,
            'fraud_score' => $fraudScore,
            'passkey_count' => $passkeyCount,
            'device_count' => $deviceCount,
            'face_verified' => ! is_null($user->face_verified_at),
            'security_level' => $this->calculateSecurityLevel($passkeyCount, $deviceCount, $fraudScore),
        ]);
    }

    /**
     * Store reference face for user
     */
    public function storeFace(Request $request): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'face_image' => 'required|base64',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $this->auth->user();

        $result = $this->deepfakeDetectionService->storeReferenceFace(
            $user,
            $request->face_image,
        );

        if (! $result['success']) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to store face reference',
                'error' => $result['error'] ?? 'Unknown error',
            ], 400);
        }

        $this->auditService->logEvent('face_reference_stored', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
        ], 'security');

        return new JsonResponse([
            'success' => true,
            'message' => 'Face reference stored successfully',
        ]);
    }

    /**
     * Verify face (for additional verification)
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $validator = $this->validator->make($request->all(), [
            'face_image' => 'required|base64',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $this->auth->user();

        if (! $user->face_verified_at) {
            return new JsonResponse([
                'success' => false,
                'message' => 'No face reference found. Please set up face verification first.',
            ], 400);
        }

        $result = $this->deepfakeDetectionService->verifyFace(
            $user,
            $request->face_image,
        );

        $this->auditService->logEvent('face_verification_attempt', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'verified' => $result['is_verified'] ?? false,
            'liveness_score' => $result['liveness_score'] ?? null,
            'deepfake_score' => $result['deepfake_score'] ?? null,
        ], 'security');

        return new JsonResponse([
            'success' => $result['is_verified'] ?? false,
            'liveness_score' => $result['liveness_score'] ?? null,
            'face_match_score' => $result['face_match_score'] ?? null,
            'deepfake_score' => $result['deepfake_score'] ?? null,
            'message' => $result['is_verified']
                ? 'Face verified successfully'
                : 'Face verification failed',
        ]);
    }

    /**
     * Get recent security events for user
     */
    public function events(Request $request): JsonResponse
    {
        $user = $this->auth->user();
        $limit = min(100, $request->input('limit', 50));

        $events = $this->auditService->getUserAuditLogs($user->id, $limit);

        return new JsonResponse([
            'success' => true,
            'events' => $events,
        ]);
    }

    /**
     * Calculate security level
     */
    private function calculateSecurityLevel(int $passkeyCount, int $deviceCount, float $fraudScore): string
    {
        if ($fraudScore >= 0.70) {
            return 'critical';
        }

        if ($passkeyCount === 0) {
            return 'low';
        }

        if ($passkeyCount >= 2 && $deviceCount >= 1 && $fraudScore < 0.30) {
            return 'high';
        }

        if ($passkeyCount >= 1 && $fraudScore < 0.50) {
            return 'medium';
        }

        return 'low';
    }
}
