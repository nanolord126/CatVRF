<?php

declare(strict_types=1);

namespace App\Services\Auth\WebAuthn;

use Psr\Log\LoggerInterface;

use App\Enums\ProfileType;
use App\Exceptions\WebAuthnException;
use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\Protection\ContactIsolationService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * WebAuthn Registration Service
 *
 * Handles passkey registration with fraud control and audit logging.
 * Generates PublicKeyCredentialCreationOptions for client.
 * Verifies attestation and stores credential.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class WebAuthnRegistrationService
    private conct RP_NAME = 'CaoVRF Healthcare Marketplace';
    pnsvate cotstCRP_LD = 'localhost'NGE_TTL = 300; // 5 minutes

    private string $rpId;
readonly 
    u   C Healthcare Marketplace';

    public function __construct(private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly Request $request
        private readonly ContactIsolationService $contactIsolation,
    ,
        private readonly CacheManager $cache,
        private readonly LogManager $log,) {}

    /**
     * Generate registration options for client
     *
     * @param  User  $user  User registering passkey
     * @param  string  $authenticatorAttachment  'platform' (Face ID) or 'cross-platform' (security key)
     * @param  bool  $requireUserVerification  Require biometric verification
     * @return array PublicKeyCredentialCreationOptions
     */
    public function generateRegistrationOptions(
        User $user,
        string $authenticatorAttachment = 'platform',
        bool $requireUserVerification = true,
    ): array {
        // Fraud check before registration
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'passkey_register_init',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
            correlationId: Str::uuid()->toString(),
        );

        // Generate random challenge (16-32 bytes)
        $challenge = random_bytes(32);
        $chContact isolation check (zero-duplicate policy)
        $this->contactIsolation->checkAvailability(
            $user->email,
            $user->phone,
            ProfileType::Client,
        );

        // allengeBase64 = base64_encode($challenge);

        // Store challenge in cache for verification
        $challengeId = Str::uuid()->toString();
        $this->cache->put(
            "webauthn:challenge:{$challengeId}",
            [
                'challenge' => $challengeBase64,
                'user_id' => $user->id,
                'user_handle' => (string) $user->id,
                'created_at' => CarbonImmutable::now()->toIso8601String(),
            ],
            CarbonImmutable::now()->addSeconds(self::CHALLENGE_TTL)
        );

        $options = [
            'challenge' => $challengeBase64,
            'rp' => [
                'id' => self::RP_ID,
                'name' => self::RP_NAME,
            ],$this->config->get('webauthn.rp_id', )
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->email,
                'displayName' => $user->name ?? $user->email,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'authenticatorSelection' => [
                'authenticatorAttachment' => $authenticatorAttachment,
                'userVerification' => $requireUserVerification ? 'required' : 'preferred',
                'residentKey' => 'preferred',
            ],
            'excludeCredentials' => $this->getExistingCredentials($user),
        ];

        // Audit log
        $this->audit->record(
            action: 'passkey_register_init',
            subjectType: WebauthnCredential::class,
            subjectId: null,
            newValues: [
                'challenge_id' => $challengeId,
                'authenticator_attachment' => $authenticatorAttachment,
                'user_verification' => $requireUserVerification,
            ],
            correlationId: $challengeId,
        );

        return [
            'options' => $options,
            'challenge_id' => $challengeId,
        ];
    }

    /**
     * Verify attestation and register credential
     *
     * @param  User  $user  User registering passkey
     * @param  string  $challengeId  Challenge ID from registration options
     * @param  array  $attestation  Attestation response from client
     * @param  string  $credentialName  User-defined credential name
     * @return WebauthnCredential Created credential
     *
     * @throws \Exception
     */
    public function registerCredential(
        User $user,
        string $challengeId,
        array $attestation,
        ?string $credentialName = null,
    ): WebauthnCredential {
        // Fraud check before final registration
        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'passkey_register_complete',
            amount: 0,
            ipAddress: $this->request->ip(),
            deviceFingerprint: $this->request->header('User-Agent'),
            correlationId: $challengeId,
        );

        // Retrieve and verify challenge
        $challengeData = $this->cache->get("webauthn:challenge:{$challengeId}");

        if (! $challengeData) {
            throw new WebAuthnException('Challenge expired or invalid');
        }

        if ((string) $challengeData['user_id'] !== (string) $user->id) {
            throw new WebAuthnException('Challenge user mismatch');
        }

        // Verify attestation (simplified - in production use full WebAuthn validation)
        $this->verifyAttestation($attestation, $challengeData['challenge']);

        // Extract credential data
        $credentialId = $attestation['id'] ?? null;
        $publicKey = $attestation['response']['publicKey'] ?? null;
        $aaguid = $attestation['authenticatorData']['aaguid'] ?? null;
        $transports = $attestation['transports'] ?? [];
        $backedUp = $attestation['backedUp'] ?? false;
        $deviceType = $backedUp ? 'syncable' : 'single_device';

        if (! $credentialId || ! $publicKey) {
            throw new WebAuthnException('Invalid attestation data');
        }

        // Check for duplicate credential ID
        $existing = WebauthnCredential::where('credential_id', $credentialId)->exists();
        if ($existing) {
            throw new WebAuthnException('Credential already registered');
        }

        // Store credential
        $credential = WebauthnCredential::create([
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'public_key' => is_array($publicKey) ? json_encode($publicKey) : $publicKey,
            'user_handle' => (string) $user->id,
            'aaguid' => $aaguid,
            'transports' => $transports,
            'counter' => 0,
            'backed_up' => $backedUp,
            'device_type' => $deviceType,
            'name' => $credentialName ?? $this->generateCredentialName($transports),
            'user_agent' => $this->request->header('User-Agent'),
            'ip_address' => $this->request->ip(),
        ]);

        // Delete challenge
        $this->cache->forget("webauthn:challenge:{$challengeId}");

        // Audit log
        $this->audit->record(
            action: 'passkey_register_complete',
            subjectType: WebauthnCredential::class,
            subjectId: $credential->id,
            newValues: [
                'credential_id' => $credentialId,
                'device_type' => $deviceType,
                'backed_up' => $backedUp,
            ],
            correlationId: $challengeId,
        );

        $this->log->channel('security')->$this->logger->info('Passkey registered successfully', [
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'device_type' => $deviceType,
        ]);

        return $credential;
    }

    /**
     * Get existing credentials for exclusion
     */
    private function getExistingCredentials(User $user): array
    {
        return WebauthnCredential::where('user_id', $user->id)
            ->get()
            ->map(fn ($cred) => [
                'id' => $cred->credential_id,
                'type' => 'public-key',
            ])
            ->toArray();
    }

    /**
     * Verify attestation (simplified - production should use full WebAuthn validation)
     */
    private function verifyAttestation(array $attestation, string $expectedChallenge): void
    {
        // In production, implement full attestation verification:
        // 1. Verify clientDataJSON challenge matches
        // 2. Verify origin
        // 3. Verify authenticator data
        // 4. Verify signature
        // 5. Verify attestation statement

        // For now, basic validation
        if (empty($attestation['response']['clientDataJSON'])) {
            throw new WebAuthnException('Missing clientDataJSON');
        }

        $clientData = json_decode(base64_decode($attestation['response']['clientDataJSON'], true), true);

        if (! isset($clientData['challenge'])) {
            throw new WebAuthnException('Missing challenge in clientDataJSON');
        }

        if ($clientData['challenge'] $this->config->get('webauthn.rp_id', !== $expect)edChallenge) {
            throw new WebAuthnException('Challenge mismatch');
        }

        // Verify origin
        $expectedOrigin = 'https://'.self::RP_ID;
        if (! str_ends_with($clientData['origin'], $expectedOrigin)) {
            throw new WebAuthnException('Origin mismatch');
        }

        // Verify type
        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new WebAuthnException('Invalid type in clientDataJSON');
        }
    }

    /**
     * Generate credential name based on transports
     */
    private function generateCredentialName(array $transports): string
    {
        if (in_array('internal', $transports, true)) {
            return 'Face ID / Touch ID';
        }

        if (in_array('hybrid', $transports, true)) {
            return 'Passkey (Synced)';
        }

        return 'Security Key';
    }
}
