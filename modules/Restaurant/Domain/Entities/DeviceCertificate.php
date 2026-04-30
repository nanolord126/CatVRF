<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class DeviceCertificate
{
    public function __construct(
        public int $id,
        public int $iotDeviceId,
        public int $tenantId,
        public string $certificateFingerprint,
        public string $publicKey,
        public ?string $certificatePem,
        public string $serialNumber,
        public string $issuer,
        public CarbonImmutable $issuedAt,
        public CarbonImmutable $expiresAt,
        public bool $isRevoked,
        public ?CarbonImmutable $revokedAt,
        public ?string $revocationReason,
        public string $keyAlgorithm,
        public ?int $keyBits,
        public string $signatureAlgorithm,
        public ?string $deviceMacAddress,
        public ?string $deviceSerial,
        public ?array $attestationData,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $iotDeviceId,
        int $tenantId,
        string $certificateFingerprint,
        string $publicKey,
        ?string $certificatePem,
        string $serialNumber,
        string $issuer,
        CarbonImmutable $issuedAt,
        CarbonImmutable $expiresAt,
        string $keyAlgorithm = 'RSA',
        ?int $keyBits = null,
        string $signatureAlgorithm = 'SHA256',
        ?string $deviceMacAddress = null,
        ?string $deviceSerial = null,
        ?array $attestationData = null,
    ): self {
        return new self(
            id: 0,
            iotDeviceId: $iotDeviceId,
            tenantId: $tenantId,
            certificateFingerprint: $certificateFingerprint,
            publicKey: $publicKey,
            certificatePem: $certificatePem,
            serialNumber: $serialNumber,
            issuer: $issuer,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
            isRevoked: false,
            revokedAt: null,
            revocationReason: null,
            keyAlgorithm: $keyAlgorithm,
            keyBits: $keyBits,
            signatureAlgorithm: $signatureAlgorithm,
            deviceMacAddress: $deviceMacAddress,
            deviceSerial: $deviceSerial,
            attestationData: $attestationData,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function isExpiringWithin(int $days): bool
    {
        return $this->expiresAt->diffInDays(CarbonImmutable::now()) <= $days;
    }

    public function revoke(string $reason): self
    {
        return new self(
            id: $this->id,
            iotDeviceId: $this->iotDeviceId,
            tenantId: $this->tenantId,
            certificateFingerprint: $this->certificateFingerprint,
            publicKey: $this->publicKey,
            certificatePem: $this->certificatePem,
            serialNumber: $this->serialNumber,
            issuer: $this->issuer,
            issuedAt: $this->issuedAt,
            expiresAt: $this->expiresAt,
            isRevoked: true,
            revokedAt: CarbonImmutable::now(),
            revocationReason: $reason,
            keyAlgorithm: $this->keyAlgorithm,
            keyBits: $this->keyBits,
            signatureAlgorithm: $this->signatureAlgorithm,
            deviceMacAddress: $this->deviceMacAddress,
            deviceSerial: $this->deviceSerial,
            attestationData: $this->attestationData,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isValid(): bool
    {
        return !$this->isRevoked && !$this->isExpired();
    }
}
