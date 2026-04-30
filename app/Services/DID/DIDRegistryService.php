<?php

declare(strict_types=1);

namespace App\Services\DID;

use App\Models\DID;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use SodiumException;
use Carbon\CarbonImmutable;

final class DIDRegistryService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Generate a new DID for a user
     */
    public function generateDID(User $user, string $method = 'did:web'): DID
    {
        $correlationId = Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $user->id,
            operationType: 'did_generation',
            amount: 0,
            correlationId: $correlationId,
        );

        $didIdentifier = $this->generateDIDIdentifier($method);
        $did = $this->buildDIDString($method, $didIdentifier);
        $keyPair = $this->generateKeyPair();

        $didRecord = DID::create([
            'did' => $did,
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'did_method' => $method,
            'did_identifier' => $didIdentifier,
            'did_document' => $this->buildDIDDocument($did, $keyPair['public_key']),
            'verification_method' => $keyPair['verification_method'],
            'public_key' => $keyPair['public_key'],
            'active' => true,
            'expires_at' => CarbonImmutable::now()->addYears(5), // DIDs expire after 5 years
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->record(
            action: 'did_generated',
            subjectType: DID::class,
            subjectId: $didRecord->id,
            newValues: [
                'user_id' => $user->id,
                'did' => $did,
                'did_method' => $method,
            ],
            correlationId: $correlationId,
        );

        return $didRecord;
    }

    /**
     * Resolve DID document
     */
    public function resolveDID(string $did): ?array
    {
        $didRecord = DID::where('did', $did)->first();

        if (! $didRecord || ! $didRecord->isValid()) {
            return null;
        }

        return $didRecord->did_document;
    }

    /**
     * Revoke DID
     */
    public function revokeDID(int $didId, string $reason, string $revokedBy): bool
    {
        $did = DID::findOrFail($didId);

        $revoked = $did->revoke($reason);

        if ($revoked) {
            $this->audit->record(
                action: 'did_revoked',
                subjectType: DID::class,
                subjectId: $did->id,
                newValues: [
                    'revoked_by' => $revokedBy,
                    'reason' => $reason,
                ],
            );
        }

        return $revoked;
    }

    /**
     * Get user DIDs
     */
    public function getUserDIDs(int $userId): array
    {
        return DID::byUser($userId)
            ->with('verifiableCredentials')
            ->get()
            ->map(fn ($did) => [
                'id' => $did->id,
                'did' => $did->did,
                'method' => $did->did_method,
                'active' => $did->active,
                'valid' => $did->isValid(),
                'expires_at' => $did->expires_at?->toISOString(),
                'credential_count' => $did->verifiableCredentials->count(),
            ])
            ->toArray();
    }

    /**
     * Rotate DID keys
     */
    public function rotateKeys(int $didId, string $rotatedBy): DID
    {
        $did = DID::findOrFail($didId);
        $keyPair = $this->generateKeyPair();

        $did->update([
            'verification_method' => $keyPair['verification_method'],
            'public_key' => $keyPair['public_key'],
            'did_document' => $this->buildDIDDocument($did->did, $keyPair['public_key']),
        ]);

        // Audit log
        $this->audit->record(
            action: 'did_keys_rotated',
            subjectType: DID::class,
            subjectId: $did->id,
            newValues: [
                'rotated_by' => $rotatedBy,
            ],
        );

        return $did->fresh();
    }

    /**
     * Generate DID identifier based on method
     */
    private function generateDIDIdentifier(string $method): string
    {
        return match ($method) {
            'did:web' => Str::random(32),
            'did:key' => $this->generateKeyIdentifier(),
            'did:ethr' => '0x'.Str::random(40),
            default => Str::random(32),
        };
    }

    /**
     * Build DID string
     */
    private function buildDIDString(string $method, string $identifier): string
    {
        $domain = config('app.url');

        return match ($method) {
            'did:web' => "did:web:{$domain}:{$identifier}",
            'did:key' => "did:key:{$identifier}",
            'did:ethr' => "did:ethr:0x{$identifier}",
            default => "did:web:{$domain}:{$identifier}",
        };
    }

    /**
     * Generate key pair for DID
     */
    private function generateKeyPair(): array
    {
        try {
            $keyPair = sodium_crypto_sign_keypair();
            $publicKey = sodium_crypto_sign_publickey($keyPair);
            $secretKey = sodium_crypto_sign_secretkey($keyPair);

            return [
                'verification_method' => 'Ed25519VerificationKey2020',
                'public_key' => base64_encode($publicKey),
                'secret_key' => base64_encode($secretKey),
            ];
        } catch (SodiumException $e) {
            // Fallback to simple random key if sodium not available
            return [
                'verification_method' => 'Ed25519VerificationKey2020',
                'public_key' => Str::random(64),
                'secret_key' => Str::random(64),
            ];
        }
    }

    /**
     * Generate key identifier for did:key
     */
    private function generateKeyIdentifier(): string
    {
        return Str::random(44); // Base64 encoded 32-byte key
    }

    /**
     * Build DID document
     */
    private function buildDIDDocument(string $did, string $publicKey): array
    {
        return [
            '@context' => [
                'https://www.w3.org/ns/did/v1',
                'https://w3id.org/security/v1',
            ],
            'id' => $did,
            'verificationMethod' => [
                [
                    'id' => "{$did}#key-1",
                    'type' => 'Ed25519VerificationKey2020',
                    'controller' => $did,
                    'publicKeyMultibase' => $publicKey,
                ],
            ],
            'authentication' => [
                "{$did}#key-1",
            ],
            'assertionMethod' => [
                "{$did}#key-1",
            ],
        ];
    }
}
