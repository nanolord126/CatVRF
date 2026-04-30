<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use BehavioralBiometricsService;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\WebAuthn\WebAuthnRegistrationService;
use App\Services\Security\DeepfakeDetectionService;
use App\Services\Security\AdaptiveAuthService;
use App\Services\Fraud\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Services\Security\BehavioralBiometricsService;

final class RegistrationController extends Controller
{
    public function __construct(private readonly BehavioralBiometricsService $behavioralBiometricsService,
        private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly RegistrationService $registration,
        private readonly WebAuthnRegistrationService $webAuthnRegistration,
        private readonly DeepfakeDetectionService $deepfakeDetection,
        private readonly AdaptiveAuthService $adaptiveAuth,
        private readonly FraudControlService $fraudControl,) {}

    /**
     * Register new user (Passwordless-first with AI verification - 2026 Standard)
     * POST /api/v1/auth/register
     *
     * 2026 Security Enhancements:
     * - Passwordless-first (Passkeys primary, password optional fallback)
     * - AI face verification for identity proofing
     * - Behavioral signal collection from first interaction
     * - Risk-based step-up for suspicious registrations
     * - Fraud control integration
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['nullable', Password::defaults()], // Optional for 2026 passwordless-first
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'invite_code' => ['nullable', 'string'],
            'face_image_base64' => ['nullable', 'string'], // AI face verification
            'behavioral_signals' => ['nullable', 'array'], // Behavioral biometrics
        ]);

        $validated['correlation_id'] = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $deviceFingerprint = $request->header('X-Device-Fingerprint');

        // 2026: Fraud control check before registration
        $fraudCheck = $this->fraudControl->check(
            userId: 0, // New user, no ID yet
            operationType: 'registration',
            amount: 0,
            ipAddress: $ipAddress,
            deviceFingerprint: $deviceFingerprint,
            correlationId: $validated['correlation_id'],
            context: [
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ],
        );

        if ($fraudCheck['is_blocked'] ?? false) {
            $this->log->warning('Registration blocked by fraud control', [
                'email' => $validated['email'],
                'correlation_id' => $validated['correlation_id'],
                'reason' => $fraudCheck['reason'] ?? 'High fraud risk',
            ]);

            return new JsonResponse([
                'error' => 'Registration blocked',
                'message' => 'Unable to complete registration at this time.',
                'correlation_id' => $validated['correlation_id'],
            ], 403);
        }

        // Register user
        $user = $this->registration->registerUser($validated);

        // 2026: AI face verification if provided
        $faceVerified = false;
        if (! empty($validated['face_image_base64'])) {
            try {
                $faceResult = $this->deepfakeDetection->verifyFace(
                    $user,
                    $validated['face_image_base64']
                );

                $faceVerified = $faceResult['is_verified'] ?? false;

                // Store reference face for future verification
                if ($faceVerified) {
                    $this->deepfakeDetection->storeReferenceFace(
                        $user,
                        $validated['face_image_base64']
                    );
                }

                $this->log->$this->logger->info('Face verification during registration', [
                    'user_id' => $user->id,
                    'verified' => $faceVerified,
                    'correlation_id' => $validated['correlation_id'],
                ]);
            } catch (\Throwable $e) {
                $this->log->warning('Face verification failed during registration', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $validated['correlation_id'],
                ]);
                // Don't block registration on face verification failure
            }
        }

        // 2026: Collect behavioral signals for initial profile
        if (! empty($validated['behavioral_signals'])) {
            try {
                $behavioralService = $this->behavioralBiometricsService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
                $behavioralService->analyzeSignals(
                    $user,
                    $validated['behavioral_signals'],
                    Str::uuid()->toString()
                );
            } catch (\Throwable $e) {
                $this->log->warning('Behavioral signal collection failed during registration', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $validated['correlation_id'],
                ]);
            }
        }

        // 2026: Adaptive auth risk evaluation for new user
        $riskEvaluation = null;
        try {
            $riskEvaluation = $this->adaptiveAuth->evaluateAuthRisk(
                $user,
                $ipAddress,
                $userAgent,
                $deviceFingerprint,
                $validated['behavioral_signals'] ?? [],
                Str::uuid()->toString()
            );
        } catch (\Throwable $e) {
            $this->log->warning('Adaptive auth evaluation failed during registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'correlation_id' => $validated['correlation_id'],
            ]);
        }

        $responseData = [
            'message' => 'Registration successful. Please verify your email.',
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
            ],
            'correlation_id' => $validated['correlation_id'],
            // 2026: Security indicators
            'security' => [
                'face_verified' => $faceVerified,
                'requires_step_up' => $riskEvaluation?->requiresStepUp() ?? false,
                'risk_level' => $riskEvaluation?->riskLevel ?? 'low',
                'passwordless_enabled' => true, // 2026: Passwordless-first
            ],
        ];

        // 2026: If high risk, require additional verification
        if ($riskEvaluation && $riskEvaluation->requiresStepUp()) {
            $responseData['message'] = 'Registration successful. Additional verification required.';
            $responseData['security']['step_up_required'] = $riskEvaluation->stepUpRequired;
        }

        return new JsonResponse($responseData, 201);
    }

    /**
     * Register via social provider
     * POST /api/v1/auth/register/social
     */
    public function registerSocial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:google,yandex,vk,telegram,apple'],
            'provider_id' => ['required', 'string'],
            'provider_token' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'provider_data' => ['nullable', 'array'],
        ]);

        $validated['correlation_id'] = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

        $user = $this->registration->registerSocial($validated);

        $token = $user->createToken('Social Auth Token', ['*'], CarbonImmutable::now()->addDays(30));

        return new JsonResponse([
            'message' => 'Social registration successful.',
            'token' => $token->plainTextToken,
            'type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Verify email
     * POST /api/v1/auth/verify-email
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        try {
            $this->registration->verifyEmail($user, $request->token);

            $this->log->channel('audit')->$this->logger->info('Email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => 'Email verified successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Email verification failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Email verification failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Send phone verification code
     * POST /api/v1/auth/send-phone-code
     */
    public function sendPhoneCode(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        if (! $user->phone) {
            return new JsonResponse([
                'error' => 'Phone number not set',
                'correlation_id' => $correlationId,
            ], 422);
        }

        try {
            $code = $this->registration->sendPhoneVerificationCode($user);

            // In development, return the code for testing
            $responseData = [
                'message' => 'Verification code sent',
                'correlation_id' => $correlationId,
            ];

            if (app()->environment('local', 'testing')) {
                $responseData['code'] = $code;
            }

            return new JsonResponse($responseData);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Phone code send failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Failed to send verification code',
                'correlation_id' => $correlationId,
            ], 422);
        }
    }

    /**
     * Verify phone
     * POST /api/v1/auth/verify-phone
     */
    public function verifyPhone(VerifyPhoneRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? $request->correlation_id ?? Str::uuid()->toString();
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 401);
        }

        try {
            $this->registration->verifyPhone($user, $request->code);

            $this->log->channel('audit')->$this->logger->info('Phone verified', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'message' => 'Phone verified successfully',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Phone verification failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'error' => 'Phone verification failed',
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
}
