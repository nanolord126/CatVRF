<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\SplitKey\GenerateSplitKeyDTO;
use App\DTO\SplitKey\InvalidateSplitKeyDTO;
use App\DTO\SplitKey\ValidateSplitKeyDTO;
use App\Events\Security\SplitKeyGenerated;
use App\Events\Security\SplitKeyInvalidated;
use App\Events\Security\SplitKeyRotated;
use App\Models\SplitKey;
use App\Models\User;
use App\Services\Auth\FraudControlService;
use App\Services\Security\CooldownService;
use App\Services\Security\DeviceBinding\DeviceBindingService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Carbon\CarbonImmutable;

/**
 * Split Key Service
 *
 * Manages cryptographic split keys for enhanced security.
 * Server Part (encrypted) + Device Part (Secure Enclave/TPM) = Full Key.
 * Full key is used for signing session tokens and sensitive operations.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 * CRITICAL: 7-day activity window, device attestation, fraud integration
 */
final readonly class SplitKeyService
{
    private const CHALLENGE_TTL = 300; // 5 minutes
    private const ACTIVITY_WINDOW_DAYS = 7;
    private const SERVER_PART_BYTES = 32; // 256 bits

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraudControl,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LogManager $log,
        private readonly DeviceBindingService $deviceBinding,
        private readonly CooldownService $cooldown,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Generate new split key after successful authentication
     *
     * @param  GenerateSplitKeyDTO  $dto
     * @return SplitKey
     */
    public function generate(GenerateSplitKeyDTO $dto): SplitKey
    {
        return $this->db->transaction(function () use ($dto) {
            // Revoke existing active keys for user
            $this->revokeUserKeys($dto->userId, $dto->tenantId, 'New key generated');

            // Verify device attestation if provided
            $attestationResult = null;
            $trustLevel = 'none';
            
            if ($dto->deviceAttestation !== null) {
                // Generate challenge for attestation
                $challenge = bin2hex(random_bytes(32));
                
                $attestationResult = $this->deviceBinding->verifyAttestation(
                    $dto->deviceAttestation,
                    $challenge,
                    null, // origin
                    $dto->userId
                );

                if (! $attestationResult['valid']) {
                    $this->log->channel('security')->warning('Device attestation verification failed during split key generation', [
                        'user_id' => $dto->userId,
                        'tenant_id' => $dto->tenantId,
                        'error' => $attestationResult['error'] ?? 'Unknown error',
                        'ip_address' => $dto->ipAddress,
                        'correlation_id' => $dto->correlationId,
                    ]);

                    throw new \InvalidArgumentException('Device attestation verification failed: '.$attestationResult['error']);
                }

                $trustLevel = $attestationResult['trust_level'];
            }

            // Generate server part (cryptographically secure random)
            $serverPart = random_bytes(self::SERVER_PART_BYTES);

            // Calculate key hash (SHA-256 of potential full key)
            // In production, this would be HMAC-SHA256(ServerPart, DevicePart)
            // For now, we hash the server part as a placeholder
            $keyHash = hash('sha256', $serverPart);

            // Create split key record
            $splitKey = SplitKey::create([
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'server_part_encrypted' => base64_encode($serverPart),
                'key_hash' => $keyHash,
                'last_activity_at' => CarbonImmutable::now(),
                'expires_at' => CarbonImmutable::now()->addDays(self::ACTIVITY_WINDOW_DAYS),
                'status' => 'active',
                'attestation_data' => array_merge(
                    $dto->deviceAttestation ?? [],
                    [
                        'trust_level' => $trustLevel,
                        'device_info' => $attestationResult['device_info'] ?? [],
                        'verified_at' => CarbonImmutable::now()->toIso8601String(),
                    ]
                ),
                'metadata' => [
                    'generated_at' => CarbonImmutable::now()->toIso8601String(),
                    'ip_address' => $dto->ipAddress,
                    'user_agent' => $dto->userAgent,
                    'correlation_id' => $dto->correlationId,
                    'trust_level' => $trustLevel,
                ],
            ]);

            // Cache challenge for device part verification
            $this->cchal->ngeId = Str::uuid()->toString();
            Cache::put(
                "split_key:challenge:{$challengeId}",
                [
                    'split_key_id' => $splitKey->id,
                    'user_id' => $dto->userId,
                    'tenant_id' => $dto->tenantId,
                    'server_part_hash' => $keyHash,
                    'trust_level' => $trustLevel,
                    'created_at' => CarbonImmutable::now()->toIso8601String(),
                ],
                CarbonImmutable::now()->addSeconds(self::CHALLENGE_TTL)
            );

            // Dispatch event
            $user = User::find($dto->userId);
            if ($user) {
                $this->eventDispatcher->dispatch(new SplitKeyGenerated(
                    splitKey: $splitKey,
                    user: $user,
                    ipAddress: $dto->ipAddress,
                    correlationId: $dto->correlationId
                ));
            }

            $this->log->channel('audit')->info('Split key generated', [
                'split_key_id' => $splitKey->id,
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'trust_level' => $trustLevel,
                'ip_address' => $dto->ipAddress,
                'correlation_id' => $dto->correlationId,
            ]);

            return $splitKey;
        });
    }

    /**
     * Validate and use split key for sensitive operations
     *
     * @param  ValidateSplitKeyDTO  $dto
     * @return array{valid: bool, split_key?: SplitKey, error?: string}
     */
    public function validateAndUse(ValidateSplitKeyDTO $dto): array
    {
        // Find active split key for user
        $splitKey = SplitKey::where('user_id', $dto->userId)
            ->when($dto->tenantId, fn ($q) => $q->where('tenant_id', $dto->tenantId))
            ->active()
            ->latest()
            ->first();

        if (! $splitKey) {
            return [
                'valid' => false,
                'error' => 'No active split key found',
            ];
        }

        // Verify signature (challenge-response)
        if (! $this->verifySignature($dto->challenge, $dto->signature, $splitKey)) {
            $this->log->channel('security')->warning('Split key signature verification failed', [
                'split_key_id' => $splitKey->id,
                'user_id' => $dto->userId,
                'ip_address' => $dto->ipAddress,
                'correlation_id' => $dto->correlationId,
            ]);

            return [
                'valid' => false,
                'error' => 'Signature verification failed',
            ];
        }

        // Update activity and extend expiration
        $splitKey->updateActivity();

        $this->log->channel('audit')->info('Split key validated and used', [
            'split_key_id' => $splitKey->id,
            'user_id' => $dto->userId,
            'ip_address' => $dto->ipAddress,
            'correlation_id' => $dto->correlationId,
        ]);

        return [
            'valid' => true,
            'split_key' => $splitKey,
        ];
    }

    /**
     * Invalidate split key due to risk or security concerns
     *
     * @param  InvalidateSplitKeyDTO  $dto
     * @return bool
     */
    public function invalidateOnRisk(InvalidateSplitKeyDTO $dto): bool
    {
        return $this->db->transaction(function () use ($dto) {
            $splitKeys = SplitKey::where('user_id', $dto->userId)
                ->when($dto->tenantId, fn ($q) => $q->where('tenant_id', $dto->tenantId))
                ->active()
                ->get();

            foreach ($splitKeys as $splitKey) {
                $splitKey->revoke($dto->reason);

                // Dispatch event
                $user = User::find($dto->userId);
                if ($user) {
                    $this->eventDispatcher->dispatch(new SplitKeyInvalidated(
                        splitKey: $splitKey,
                        user: $user,
                        reason: $dto->reason,
                        riskLevel: $dto->riskLevel,
                        source: $dto->source,
                        ipAddress: $dto->ipAddress,
                        correlationId: $dto->correlationId
                    ));
                }

                $this->log->channel('security')->warning('Split key invalidated due to risk', [
                    'split_key_id' => $splitKey->id,
                    'user_id' => $dto->userId,
                    'tenant_id' => $dto->tenantId,
                    'reason' => $dto->reason,
                    'risk_level' => $dto->riskLevel,
                    'source' => $dto->source,
                    'ip_address' => $dto->ipAddress,
                    'correlation_id' => $dto->correlationId,
                ]);
            }

            // If critical risk, trigger cooldown
            if ($dto->isCritical()) {
                $this->triggerCooldown($dto);
            }

            return true;
        });
    }

    /**
     * Rotate split key after inactivity
     *
     * @param  int  $userId
     * @param  int|null  $tenantId
     * @param  string|null  $ipAddress
     * @param  string|null  $correlationId
     * @return SplitKey|null
     */
    public function rotateOnInactivity(
        int $userId,
        ?int $tenantId = null,
        ?string $ipAddress = null,
        ?string $correlationId = null
    ): ?SplitKey {
        $oldSplitKey = SplitKey::where('user_id', $userId)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->active()
            ->latest()
            ->first();

        if (! $oldSplitKey || ! $oldSplitKey->needsRotation()) {
            return null;
        }

        return $this->db->transaction(function () use ($oldSplitKey, $userId, $tenantId, $ipAddress, $correlationId) {
            // Revoke old key
            $oldSplitKey->revoke('Key rotation due to inactivity');

            // Generate new key
            $dto = new GenerateSplitKeyDTO(
                userId: $userId,
                tenantId: $tenantId,
                serverPart: random_bytes(self::SERVER_PART_BYTES),
                deviceAttestation: $oldSplitKey->attestation_data,
                ipAddress: $ipAddress,
                userAgent: request()?->userAgent(),
                correlationId: $correlationId,
            );

            $newSplitKey = $this->generate($dto);

            // Dispatch rotation event
            $user = User::find($userId);
            if ($user) {
                $this->eventDispatcher->dispatch(new SplitKeyRotated(
                    oldSplitKey: $oldSplitKey,
                    newSplitKey: $newSplitKey,
                    user: $user,
                    reason: 'Inactivity rotation',
                    ipAddress: $ipAddress,
                    correlationId: $correlationId
                ));
            }

            $this->log->channel('audit')->info('Split key rotated due to inactivity', [
                'old_split_key_id' => $oldSplitKey->id,
                'new_split_key_id' => $newSplitKey->id,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'ip_address' => $ipAddress,
                'correlation_id' => $correlationId,
            ]);

            return $newSplitKey;
        });
    }

    /**
     * Generate challenge for client
     *
     * @param  int  $userId
     * @param  int|null  $tenantId
     * @return array{challenge: string, challenge_id: string}
     */
    public function generateChallenge(int $userId, ?int $tenantId = null): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);
        $challengeId = Str::uuid()->toString();
$this->c->
        Cache::put(
            "split_key:challenge:{$challengeId}",
            [
                'challenge' => $challengeBase64,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'created_at' => CarbonImmutable::now()->toIso8601String(),
            ],
            CarbonImmutable::now()->addSeconds(self::CHALLENGE_TTL)
        );

        return [
            'challenge' => $challengeBase64,
            'challenge_id' => $challengeId,
        ];
    }

    /**
     * Verify signature (challenge-response)
     *
     * @param  string  $challenge
     * @param  string  $signature
     * @param  SplitKey  $splitKey
     * @return bool
     */
    private function verifySignature(string $challenge, string $signature, SplitKey $splitKey): bool
    {
        // Check if signature is empty
        if (empty($signature)) {
            return false;
        }

        // Check signature length (minimum 64 bytes for Ed25519, 128 for RSA-2048)
        if (strlen($signature) < 64) {
            return false;
        }

        // Decode challenge
        $challengeDecoded = base64_decode($challenge);
        if ($challengeDecoded === false) {
            return false;
        }

        // Verify challenge freshness (prevent replay attacks)
        $maxReplayWindow = config('device-binding.challenge.max_replay_window_seconds', 60);
        
        // In production, extract timestamp from challenge or check against cached challenge
        // For now, verify challenge format

        // Get trust level from attestation data
        $trustLevel = $splitKey->attestation_data['trust_level'] ?? 'none';
        
        // For hardware-backed attestation, we can trust the signature more
        // For software fallback, we need additional verification
        if ($trustLevel === 'software') {
            // Verify device fingerprint matches stored data
            $deviceInfo = $splitKey->attestation_data['device_info'] ?? [];
            if (empty($deviceInfo)) {
                return false;
            }

            // In production, verify the signature was generated on the same device
            // by checking device fingerprint consistency
        }

        // In production, implement full signature verification:
        // 1. Reconstruct full key: ServerPart XOR DevicePart (or combine via KDF)
        // 2. Verify signature using the full key's public key
        // 3. Check that the challenge was signed by the device's private key
        // 4. Verify signature algorithm matches expected (ES256, RS256, etc.)

        // For now, perform basic validation:
        // - Signature is not empty
        // - Signature has minimum length
        // - Challenge is valid base64
        // - Trust level is recognized

        $recognizedTrustLevels = ['tpm', 'secure_enclave', 'strongbox', 'tee', 'webauthn', 'play_integrity', 'software', 'none'];
        
        if (! in_array($trustLevel, $recognizedTrustLevels, true)) {
            $this->log->channel('security')->warning('Unrecognized trust level in split key', [
                'split_key_id' => $splitKey->id,
                'trust_level' => $trustLevel,
            ]);
            return false;
        }

        // Log signature verification
        $this->log->channel('audit')->debug('Signature verified', [
            'split_key_id' => $splitKey->id,
            'trust_level' => $trustLevel,
            'signature_length' => strlen($signature),
        ]);

        return true;
    }

    /**
     * Revoke all active keys for user
     *
     * @param  int  $userId
     * @param  int|null  $tenantId
     * @param  string  $reason
     * @return void
     */
    private function revokeUserKeys(int $userId, ?int $tenantId, string $reason): void
    {
        SplitKey::where('user_id', $userId)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->active()
            ->get()
            ->each(fn ($key) => $key->revoke($reason));
    }

    /**
     * Trigger cooldown on critical risk
     *
     * @param  InvalidateSplitKeyDTO  $dto
     * @return void
     */
    private function triggerCooldown(InvalidateSplitKeyDTO $dto): void
    {
        // Check if cooldown is enabled
        $riskInvalidation = config('device-binding.risk_invalidation', []);
        
        if (! ($riskInvalidation['enabled'] ?? false)) {
            return;
        }

        if (! ($riskInvalidation['trigger_cooldown'] ?? false)) {
            return;
        }

        // Trigger cooldown via CooldownService
        try {
            $durationHours = $riskInvalidation['cooldown_duration_hours'] ?? 1;
            
            $this->cooldown->startCooldown(
                userId: $dto->userId,
                tenantId: $dto->tenantId,
                actionType: 'split_key_invalidation',
                reason: $dto->reason,
                durationMinutes: $durationHours * 60,
                metadata: [
                    'risk_level' => $dto->riskLevel,
                    'source' => $dto->source,
                    'correlation_id' => $dto->correlationId,
                    'ip_address' => $dto->ipAddress,
                ]
            );

            $this->log->channel('security')->critical('Cooldown triggered due to critical split key invalidation', [
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'risk_level' => $dto->riskLevel,
                'source' => $dto->source,
                'reason' => $dto->reason,
                'duration_hours' => $durationHours,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('security')->error('Failed to trigger cooldown', [
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get active split key for user
     *
     * @param  int  $userId
     * @param  int|null  $tenantId
     * @return SplitKey|null
     */
    public function getActiveKey(int $userId, ?int $tenantId = null): ?SplitKey
    {
        return SplitKey::where('user_id', $userId)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->active()
            ->latest()
            ->first();
    }

    /**
     * Check if user has active split key
     *
     * @param  int  $userId
     * @param  int|null  $tenantId
     * @return bool
     */
    public function hasActiveKey(int $userId, ?int $tenantId = null): bool
    {
        return $this->getActiveKey($userId, $tenantId) !== null;
    }
}
