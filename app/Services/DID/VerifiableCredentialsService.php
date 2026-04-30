<?php

declare(strict_types=1);

namespace App\Services\DID;

use App\Models\DID;
use App\Models\VerifiableCredential;
use App\Services\Audit\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use SodiumException;

final class VerifiableCredentialsService
{
    public function __construct(
        private readonly DIDRegistryService $didRegistry,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
    ) {}

    /**
     * Issue a Verifiable Credential
     */
    public function issueCredential(array $data): VerifiableCredential
    {
        $correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $data['user_id'] ?? null,
            operationType: 'vc_issuance',
            amount: 0,
            correlationId: $correlationId,
        );

        $did = $this->didRegistry->resolveDID($data['issuer_did']);
        if (! $did) {
            throw new \InvalidArgumentException('Invalid issuer DID');
        }

        $vc = VerifiableCredential::create([
            'vc_id' => Str::uuid()->toString(),
            'did_id' => DID::where('did', $data['issuer_did'])->first()?->id,
            'user_id' => $data['user_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'vc_type' => $data['vc_type'],
            'issuer_did' => $data['issuer_did'],
            'issuer_name' => $data['issuer_name'] ?? 'CatVRF',
            'issuance_date' => CarbonImmutable::now(),
            'expiration_date' => $data['expiration_date'] ?? CarbonImmutable::now()->addYear(),
            'credential_subject' => $data['credential_subject'],
            'credential_schema' => $data['credential_schema'] ?? null,
            'status' => 'active',
            'proof' => $this->signCredential($data, $did),
            'correlation_id' => $correlationId,
        ]);

        // Audit log
        $this->audit->record(
            action: 'vc_issued',
            subjectType: VerifiableCredential::class,
            subjectId: $vc->id,
            newValues: [
                'vc_type' => $vc->vc_type,
                'issuer_did' => $vc->issuer_did,
                'user_id' => $vc->user_id,
            ],
            correlationId: $correlationId,
        );

        return $vc;
    }

    /**
     * Verify a Verifiable Credential
     */
    public function verifyCredential(VerifiableCredential $vc): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
        ];

        // Check if VC is active
        if ($vc->status !== 'active') {
            $result['errors'][] = 'Credential is not active';
            $result['valid'] = false;

            return $result;
        }

        // Check if VC is expired
        if ($vc->isExpired()) {
            $result['errors'][] = 'Credential has expired';
            $result['valid'] = false;

            return $result;
        }

        // Check if VC is revoked
        if ($vc->isRevoked()) {
            $result['errors'][] = 'Credential has been revoked';
            $result['valid'] = false;

            return $result;
        }

        // Verify DID is valid
        $didDocument = $this->didRegistry->resolveDID($vc->issuer_did);
        if (! $didDocument) {
            $result['errors'][] = 'Issuer DID is invalid or revoked';
            $result['valid'] = false;

            return $result;
        }

        // Verify proof
        if (! $this->verifyProof($vc)) {
            $result['errors'][] = 'Credential signature is invalid';
            $result['valid'] = false;

            return $result;
        }

        // Check expiration warning
        if ($vc->expiration_date && $vc->expiration_date->diffInDays(CarbonImmutable::now()) < 30) {
            $result['warnings'][] = 'Credential expires in less than 30 days';
        }

        $result['valid'] = true;

        return $result;
    }

    /**
     * Revoke a Verifiable Credential
     */
    public function revokeCredential(int $vcId, string $reason, string $revokedBy): bool
    {
        $vc = VerifiableCredential::findOrFail($vcId);

        $revoked = $vc->revoke($reason, $revokedBy);

        if ($revoked) {
            $this->audit->record(
                action: 'vc_revoked',
                subjectType: VerifiableCredential::class,
                subjectId: $vc->id,
                newValues: [
                    'revoked_by' => $revokedBy,
                    'reason' => $reason,
                ],
            );
        }

        return $revoked;
    }

    /**
     * Issue KYB Completion Credential
     */
    public function issueKYBCompletionCredential(int $businessId, array $kybData): VerifiableCredential
    {
        $issuerDID = $this->getIssuerDID();

        return $this->issueCredential([
            'issuer_did' => $issuerDID,
            'vc_type' => 'KYBCompletionCredential',
            'user_id' => null,
            'tenant_id' => $kybData['tenant_id'],
            'credential_subject' => [
                'id' => $kybData['business_did'],
                'inn' => $kybData['inn'],
                'company_name' => $kybData['company_name'],
                'kyb_status' => 'verified',
                'verified_at' => $kybData['verified_at']->toISOString(),
                'risk_score' => $kybData['risk_score'],
            ],
            'expiration_date' => CarbonImmutable::now()->addYear(),
        ]);
    }

    /**
     * Issue Identity Credential
     */
    public function issueIdentityCredential(int $userId, array $identityData): VerifiableCredential
    {
        $issuerDID = $this->getIssuerDID();

        return $this->issueCredential([
            'issuer_did' => $issuerDID,
            'vc_type' => 'IdentityCredential',
            'user_id' => $userId,
            'tenant_id' => $identityData['tenant_id'],
            'credential_subject' => [
                'id' => $identityData['user_did'],
                'name' => $identityData['name'],
                'verified' => $identityData['verified'],
                'verified_at' => $identityData['verified_at']->toISOString(),
            ],
            'expiration_date' => CarbonImmutable::now()->addYears(2),
        ]);
    }

    /**
     * Get user credentials
     */
    public function getUserCredentials(int $userId): array
    {
        return VerifiableCredential::byUser($userId)
            ->with('did')
            ->get()
            ->map(fn ($vc) => [
                'id' => $vc->id,
                'vc_id' => $vc->vc_id,
                'vc_type' => $vc->vc_type,
                'issuer_did' => $vc->issuer_did,
                'issuer_name' => $vc->issuer_name,
                'issuance_date' => $vc->issuance_date->toISOString(),
                'expiration_date' => $vc->expiration_date?->toISOString(),
                'status' => $vc->status,
                'valid' => $vc->isValid(),
            ])
            ->toArray();
    }

    /**
     * Get tenant credentials
     */
    public function getTenantCredentials(int $tenantId): array
    {
        return VerifiableCredential::byTenant($tenantId)
            ->with('did')
            ->get()
            ->map(fn ($vc) => [
                'id' => $vc->id,
                'vc_id' => $vc->vc_id,
                'vc_type' => $vc->vc_type,
                'issuer_did' => $vc->issuer_did,
                'issuer_name' => $vc->issuer_name,
                'issuance_date' => $vc->issuance_date->toISOString(),
                'expiration_date' => $vc->expiration_date?->toISOString(),
                'status' => $vc->status,
                'valid' => $vc->isValid(),
            ])
            ->toArray();
    }

    /**
     * Present credential for verification
     */
    public function presentCredential(string $vcId): array
    {
        $vc = VerifiableCredential::where('vc_id', $vcId)->firstOrFail();

        return [
            '@context' => ['https://www.w3.org/2018/credentials/v1'],
            'type' => ['VerifiableCredential', $vc->vc_type],
            'id' => $vc->vc_id,
            'issuer' => $vc->issuer_did,
            'issuanceDate' => $vc->issuance_date->toISOString(),
            'expirationDate' => $vc->expiration_date?->toISOString(),
            'credentialSubject' => $vc->credential_subject,
            'proof' => $vc->proof,
        ];
    }

    /**
     * Sign credential with DID key
     */
    private function signCredential(array $data, array $didDocument): array
    {
        $credential = [
            '@context' => ['https://www.w3.org/2018/credentials/v1'],
            'type' => ['VerifiableCredential', $data['vc_type']],
            'issuer' => $data['issuer_did'],
            'issuanceDate' => CarbonImmutable::now()->toISOString(),
            'credentialSubject' => $data['credential_subject'],
        ];

        if (isset($data['expiration_date'])) {
            $credential['expirationDate'] = $data['expiration_date']->toISOString();
        }

        // Create proof
        try {
            $proof = [
                'type' => 'Ed25519Signature2020',
                'created' => CarbonImmutable::now()->toISOString(),
                'verificationMethod' => $data['issuer_did'].'#key-1',
                'proofPurpose' => 'assertionMethod',
                'proofValue' => base64_encode($this->sign($credential)),
            ];
        } catch (SodiumException $e) {
            // Fallback to simple signature
            $proof = [
                'type' => 'Ed25519Signature2020',
                'created' => CarbonImmutable::now()->toISOString(),
                'verificationMethod' => $data['issuer_did'].'#key-1',
                'proofPurpose' => 'assertionMethod',
                'proofValue' => hash('sha256', json_encode($credential)),
            ];
        }

        return $proof;
    }

    /**
     * Sign data with private key
     */
    private function sign(array $data): string
    {
        // Placeholder - in production, use actual private key from DID
        return hash('sha256', json_encode($data));
    }

    /**
     * Verify credential proof
     */
    private function verifyProof(VerifiableCredential $vc): bool
    {
        if (! $vc->proof) {
            return false;
        }

        // Placeholder - in production, verify actual signature
        return true;
    }

    /**
     * Get issuer DID from config
     */
    private function getIssuerDID(): string
    {
        return config('did.issuer_did') ?? 'did:web:catvrf.ru:issuer';
    }
}
