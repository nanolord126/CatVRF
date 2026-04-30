<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Http\Controllers\Controller;
use App\Services\Auth\WebAuthn\WebAuthnRegistrationService;
use App\Services\Auth\WebAuthn\WebAuthnAuthenticationService;
use App\Services\Auth\WebAuthn\WebAuthnCredentialService;
use App\Services\Security\AccountProtectionService;
use App\Services\Security\UserDeviceService;
use App\Services\Security\AuditService;
use App\Services\Security\RecoveryService;
use App\Services\Security\AdaptiveAuthService;
use App\Services\Security\BehavioralBiometricsService;
use App\Models\WebauthnCredential;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Events\Security\PasskeyRevoked;

/**
 * Passkey Authentication Controller
 *
 * Handles passwordless authentication using WebAuthn/Passkeys.
 * Supports registration, authentication, and credential management.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class PasskeyAuthController extends Controller
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LogManager $log,
        private readonly WebAuthnRegistrationService $registrationService,
        private readonly WebAuthnAuthenticationService $authenticationService,
        private readonly WebAuthnCredentialService $credentialService,
        private readonly AccountProtectionService $accountProtectionService,
        private readonly UserDeviceService $userDeviceService,
        private readonly AuditService $auditService,
        private readonly RecoveryService $recoveryService,
        private readonly AdaptiveAuthService $adaptiveAuthService,
        private readonly BehavioralBiometricsService $behavioralBiometricsService,) {}

    /**
     * Generate registration options for new passkey
     *
     * POST /api/v1/auth/passkey/register-options
     */
    public function registerOptions(Request $request): JsonResponse
    {
        $request->validate([
            'authenticator_attachment' => 'sometimes|in:platform,cross-platform',
            'user_verification_required' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Check if account is locked
        if ($this->accountProtectionService->isAccountLocked($user)) {
            return new JsonResponse(['error' => 'Account is locked. Please contact support.'], 403);
        }

        try {
            $result = $this->registrationService->generateRegistrationOptions(
                user: $user,
                authenticatorAttachment: $request->input('authenticator_attachment', 'platform'),
                requireUserVerification: $request->input('user_verification_required', true),
            );

            $this->auditService->logPasskeyEvent('register_options_generated', $user->id, $user->tenant_id);

            return new JsonResponse($result);
        } catch (\Exception $e) {
            $this->auditService->logPasskeyEvent('register_options_failed', $user->id, $user->tenant_id);

            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Complete passkey registration
     *
     * POST /api/v1/auth/passkey/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_id' => 'required|string',
            'attestation' => 'required|array',
            'credential_name' => 'sometimes|string|max:255',
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $credential = $this->registrationService->registerCredential(
                user: $user,
                challengeId: $request->input('challenge_id'),
                attestation: $request->input('attestation'),
                credentialName: $request->input('credential_name'),
            );

            return new JsonResponse([
                'message' => 'Passkey registered successfully',
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'device_type' => $credential->device_type,
                    'backed_up' => $credential->backed_up,
                    'created_at' => $credential->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Generate authentication options
     *
     * POST /api/v1/auth/passkey/login-options
     */
    public function loginOptions(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $result = $this->authenticationService->generateAuthenticationOptions(
                email: $request->input('email'),
            );

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Complete passkey authentication with adaptive step-up (2026 Standard)
     *
     * POST /api/v1/auth/passkey/login
     *
     * 2026 Security Enhancements:
     * - Adaptive risk-based authentication
     * - Behavioral signal collection
     * - Step-up challenges for elevated risk
     * - Device reputation checking
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_id' => 'required|string',
            'assertion' => 'required|array',
            'behavioral_signals' => ['nullable', 'array'], // 2026: Behavioral biometrics
        ]);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $deviceFingerprint = $request->header('X-Device-Fingerprint');
        $sessionId = $request->header('X-Session-ID') ?? Str::uuid()->toString();

        try {
            // Standard passkey authentication
            $result = $this->authenticationService->authenticate(
                challengeId: $request->input('challenge_id'),
                assertion: $request->input('assertion'),
            );

            $user = $result['user'];

            // 2026: Collect behavioral signals
            $behavioralData = [];
            if (! empty($request->input('behavioral_signals'))) {
                try {
                    $behavioralResult = $this->behavioralBiometricsService->analyzeSignals(
                        $user,
                        $request->input('behavioral_signals'),
                        $sessionId
                    );
                    $behavioralData = $behavioralResult;
                } catch (\Throwable $e) {
                    $this->log->warning('Behavioral analysis failed during login', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 2026: Adaptive auth risk evaluation
            $riskEvaluation = null;
            try {
                $riskEvaluation = $this->adaptiveAuthService->evaluateAuthRisk(
                    $user,
                    $ipAddress,
                    $userAgent,
                    $deviceFingerprint,
                    $request->input('behavioral_signals') ?? [],
                    $sessionId
                );
            } catch (\Throwable $e) {
                $this->log->warning('Adaptive auth evaluation failed during login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Build response
            $responseData = [
                'message' => 'Authentication successful',
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                ],
                'token' => $result['token'],
                'credential' => [
                    'id' => $result['credential']->id,
                    'name' => $result['credential']->name,
                    'last_used_at' => $result['credential']->last_used_at,
                ],
                // 2026: Security indicators
                'security' => [
                    'session_id' => $sessionId,
                    'risk_level' => $riskEvaluation?->riskLevel ?? 'low',
                    'requires_step_up' => $riskEvaluation?->requiresStepUp() ?? false,
                ],
            ];

            // 2026: If step-up required, return challenge details
            if ($riskEvaluation && $riskEvaluation->requiresStepUp()) {
                $responseData['message'] = 'Authentication successful. Additional verification required.';
                $responseData['security']['step_up_required'] = $riskEvaluation->stepUpRequired;
                $responseData['security']['step_up_correlation_id'] = $riskEvaluation->correlationId;
                $responseData['security']['step_up_message'] = $riskEvaluation->getStepUpMessage();

                // Revoke token until step-up completed
                $user->tokens()->delete();
                unset($responseData['token']);
            }

            return new JsonResponse($responseData);
        } catch (\Exception $e) {
            $this->log->error('Passkey authentication failed', [
                'error' => $e->getMessage(),
                'ip' => $ipAddress,
            ]);

            return new JsonResponse(['error' => $e->getMessage()], 401);
        }
    }

    /**
     * List user's passkeys
     *
     * GET /api/v1/auth/passkey/credentials
     */
    public function listCredentials(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $credentials = $this->credentialService->getUserCredentials($user);

        return new JsonResponse([
            'credentials' => $credentials->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'device_type' => $c->device_type,
                'device_type_label' => $c->getDeviceTypeLabel(),
                'backed_up' => $c->backed_up,
                'is_platform_authenticator' => $c->isPlatformAuthenticator(),
                'is_synced' => $c->isSynced(),
                'last_used_at' => $c->last_used_at,
                'created_at' => $c->created_at,
            ]),
            'stats' => $this->credentialService->getCredentialStats($user),
        ]);
    }

    /**
     * Get single credential
     *
     * GET /api/v1/auth/passkey/credentials/{id}
     */
    public function getCredential(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $credential = $this->credentialService->getCredential($user, $id);

            return new JsonResponse([
                'id' => $credential->id,
                'name' => $credential->name,
                'device_type' => $credential->device_type,
                'device_type_label' => $credential->getDeviceTypeLabel(),
                'backed_up' => $credential->backed_up,
                'is_platform_authenticator' => $credential->isPlatformAuthenticator(),
                'is_synced' => $credential->isSynced(),
                'transports' => $credential->transports,
                'last_used_at' => $credential->last_used_at,
                'created_at' => $credential->created_at,
                'user_agent' => $credential->user_agent,
                'ip_address' => $credential->ip_address,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Rename credential
     *
     * PUT /api/v1/auth/passkey/credentials/{id}
     */
    public function renameCredential(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $credential = $this->credentialService->renameCredential(
                user: $user,
                credentialId: $id,
                name: $request->input('name'),
            );

            return new JsonResponse([
                'message' => 'Credential renamed successfully',
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete credential
     *
     * DELETE /api/v1/auth/passkey/credentials/{id}
     */
    public function deleteCredential(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'revoke_sessions' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        try {
            $credential = WebauthnCredential::where('id', $id)
                ->where('user_id', $user->id)
                ->where('tenant_id', $user->tenant_id)
                ->firstOrFail();

            $this->credentialService->deleteCredential(
                user: $user,
                credentialId: $id,
                revokeSessions: $request->input('revoke_sessions', true),
            );

            // Fire PasskeyRevoked event
            $this->eventDispatcher->dispatch(new PasskeyRevoked($user, $credential, 'user_initiated'));

            $this->auditService->logPasskeyEvent('deleted', $user->id, $user->tenant_id, $credential->id);

            return new JsonResponse(['message' => 'Credential deleted successfully']);
        } catch (\Exception $e) {
            $this->auditService->logPasskeyEvent('deletion_failed', $user->id, $user->tenant_id, (string) $id);

            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
