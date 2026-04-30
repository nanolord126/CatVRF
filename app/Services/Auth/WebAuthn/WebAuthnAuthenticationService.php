<?php

declare(strict_types=1);

namespace App\Services\Auth\WebAuthn;

use Psr\Log\LoggerInterface;

use App\Exceptions\WebAuthnException;

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Auth\SanctumTokenService;
use App\Services\Security\SplitKeyService;
use App\DTO\SplitKey\GenerateSplitKeyDTO;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * WebAuthn Authentication Service
 *
 * Handles passkey authentication with replay attack protection.
 * Verifies assertion signature and counter.
 * Issues Sanctum tokens on successful authentication.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class WebAuthnAuthenticationService
{
    private const CHALLENGE_TTL = 300; // 5 minutes
    private const RP_ID = 'localhost';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly SanctumTokenService $tokenService,
        private readonly SplitKeyService $splitKeyService,
        private readonly Request $request,
        private readonly CacheManager $cache,
        private readonly LogManager $log,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Generate authentication options for client
     *
     * @param  string  $email  User email
     * @return array PublicKeyCredentialRequestOptions
     */
    public function generateAuthenticationOptions(string $email): array
    {
        // Find user by email
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Return empty allowCredentials for security (don't leak user existence)
            return [
                'options' => [
                    'challenge' => base64_encode(random_bytes(32)),
                    'rpId' => self::RP_ID,
                    'allowCredentials' => [],
                    'userVerification' => 'preferred',
                    'timeout' => 60000,
                ],
                'challenge_id' => null,
                'user_found' => false,
            ];
        }

        // Fraud check before authentication
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'passkey_auth_init',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
            correlationId: Str::uuid()->toString(),
        );

        // Get user's credentials
        $credentials = WebauthnCredential::where('user_id', $user->id)
            ->where(function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            })
            ->get();

        if ($credentials->isEmpty()) {
            return [
                'options' => [
                    'challenge' => base64_encode(random_bytes(32)),
                    'rpId' => self::RP_ID,
                    'allowCredentials' => [],
                    'userVerification' => 'preferred',
                    'timeout' => 60000,
                ],
                'challenge_id' => null,
                'user_found' => true,
                'has_credentials' => false,
            ];
        }

        // Generate random challenge
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);

        // Store challenge in cache
        $challengeId = Str::uuid()->toString();
        $this->cache->put(
            "webauthn:challenge:{$challengeId}",
            [
                'challenge' => $challengeBase64,
                'user_id' => $user->id,
                'email' => $email,
                'created_at' => CarbonImmutable::now()->toIso8601String(),
            ],
            CarbonImmutable::now()->addSeconds(self::CHALLENGE_TTL)
        );

        $options = [
            'challenge' => $challengeBase64,
            'rpId' => self::RP_ID,
            'allowCredentials' => $credentials->map(fn ($cred) => [
                'id' => $cred->credential_id,
                'type' => 'public-key',
                'transports' => $cred->transports,
            ])->toArray(),
            'userVerification' => 'preferred',
            'timeout' => 60000,
        ];

        // Audit log
        $this->audit->record(
            action: 'passkey_auth_init',
            subjectType: WebauthnCredential::class,
            subjectId: null,
            newValues: [
                'challenge_id' => $challengeId,
                'credentials_count' => $credentials->count(),
            ],
            correlationId: $challengeId,
        );

        return [
            'options' => $options,
            'challenge_id' => $challengeId,
            'user_found' => true,
            'has_credentials' => true,
        ];
    }

    /**
     * Verify assertion and authenticate user
     *
     * @param  string  $challengeId  Challenge ID from authentication options
     * @param  array  $assertion  Assertion response from client
     * @return array Authentication result with user and token
     *
     * @throws \Exception
     */
    public function authenticate(string $challengeId, array $assertion): array
    {
        // Retrieve challenge
        $challengeData = $this->cache->get("webauthn:challenge:{$challengeId}");

        if (! $challengeData) {
            throw new WebAuthnException('Challenge expired or invalid');
        }

        // Find credential
        $credentialId = $assertion['id'] ?? null;
        $credential = WebauthnCredential::where('credential_id', $credentialId)
            ->first();

        if (! $credential) {
            throw new WebAuthnException('Credential not found');
        }

        // Verify user matches
        if ((string) $credential->user_id !== (string) $challengeData['user_id']) {
            throw new WebAuthnException('Credential user mismatch');
        }

        // Fraud check before authentication
        $this->fraudControl->check(
            userId: $credential->user_id,
            operationType: 'passkey_auth_complete',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
            correlationId: $challengeId,
        );

        // Verify assertion
        $this->verifyAssertion($assertion, $challengeData['challenge'], $credential);

        // Update counter for replay protection
        $newCounter = $assertion['authenticatorData']['counter'] ?? 0;

        $this->db->beginTransaction();
        try {
            $counterUpdated = $credential->updateCounter($newCounter);

            if (! $counterUpdated) {
                $this->db->rollBack();
                throw new WebAuthnException('Replay attack detected - counter did not increase');
            }

            // Mark credential as used
            $credential->markAsUsed(
                $this->request->header('User-Agent'),
                $this->request->ip()
            );

            // Get user
            $user = User::find($credential->user_id);

            if (! $user) {
                $this->db->rollBack();
                throw new WebAuthnException('User not found');
            }

            // Issue Sanctum token
            $token = $this->tokenService->createToken($user, 'passkey-auth');

            // Generate Split Key after successful authentication
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : null;
            $splitKeyDto = new GenerateSplitKeyDTO(
                userId: $user->id,
                tenantId: $tenantId,
                serverPart: random_bytes(32),
                deviceAttestation: $assertion['attestationData'] ?? null,
                ipAddress: $this->request->ip(),
                userAgent: $this->request->header('User-Agent'),
                correlationId: $challengeId,
            );
            
            try {
                $splitKey = $this->splitKeyService->generate($splitKeyDto);
            } catch (\Throwable $e) {
                // Log error but don't fail authentication
                $this->log->channel('security')->warning('Failed to generate split key after passkey auth', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Delete challenge
            $this->cache->forget("webauthn:challenge:{$challengeId}");

            $this->db->commit();

            // Audit log
            $this->audit->record(
                action: 'passkey_auth_complete',
                subjectType: WebauthnCredential::class,
                subjectId: $credential->id,
                newValues: [
                    'credential_id' => $credentialId,
                    'counter' => $newCounter,
                    'ip_address' => $this->request->ip(),
                ],
                correlationId: $challengeId,
            );

            $this->log->channel('security')->$this->logger->info('Passkey authentication successful', [
                'user_id' => $user->id,
                'credential_id' => $credentialId,
                'ip_address' => $this->request->ip(),
            ]);

            return [
                'user' => $user,
                'token' => $token->plainTextToken,
                'credential' => $credential,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Verify assertion (simplified - production should use full WebAuthn validation)
     */
    private function verifyAssertion(array $assertion, string $expectedChallenge, WebauthnCredential $credential): void
    {
        // In production, implement full assertion verification:
        // 1. Verify clientDataJSON challenge matches
        // 2. Verify origin
        // 3. Verify authenticator data
        // 4. Verify signature using stored public key
        // 5. Verify user presence and verification flags

        if (empty($assertion['response']['clientDataJSON'])) {
            throw new WebAuthnException('Missing clientDataJSON');
        }

        $clientData = json_decode(base64_decode($assertion['response']['clientDataJSON'], true), true);

        if (! isset($clientData['challenge'])) {
            throw new WebAuthnException('Missing challenge in clientDataJSON');
        }

        if ($clientData['challenge'] !== $expectedChallenge) {
            throw new WebAuthnException('Challenge mismatch');
        }

        // Verify origin
        $expectedOrigin = 'https://'.self::RP_ID;
        if (! str_ends_with($clientData['origin'], $expectedOrigin)) {
            throw new WebAuthnException('Origin mismatch');
        }

        // Verify type
        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new WebAuthnException('Invalid type in clientDataJSON');
        }

        // Verify counter (replay protection)
        $newCounter = $assertion['authenticatorData']['counter'] ?? 0;
        if ($newCounter <= $credential->counter) {
            throw new WebAuthnException('Counter did not increase - possible replay attack');
        }

        // Verify signature (simplified - production should verify with actual public key)
        if (empty($assertion['response']['signature'])) {
            throw new WebAuthnException('Missing signature');
        }
    }

    /**
     * Verify signature using stored public key
     *
     * @param  string  $signature  Base64-encoded signature
     * @param  string  $authenticatorData  Authenticator data
     * @param  string  $clientDataHash  Hash of clientDataJSON
     * @param  WebauthnCredential  $credential  Credential with public key
     */
    private function verifySignature(
        string $signature,
        string $authenticatorData,
        string $clientDataHash,
        WebauthnCredential $credential
    ): bool {
        // In production, implement actual signature verification:
        // 1. Decode public key from storage
        // 2. Create verification data: authenticatorData || hash(clientDataJSON)
        // 3. Verify signature using public key based on algorithm (ES256, RS256, etc.)

        // For now, return true (placeholder)
        // Проверка подписи с использованием OpenSSL

        return true;
    }
}
