<?php

declare(strict_types=1);

namespace Modules\Restaurant\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Restaurant\Domain\Entities\DeviceCertificate as DeviceCertificateEntity;
use Modules\Restaurant\Domain\Entities\IoTDevice as IoTDeviceEntity;
use Modules\Restaurant\Infrastructure\Repositories\EloquentDeviceCertificateRepository;

final class DeviceCertificateModel extends Model
{
    use SoftDeletes;

    protected $table = 'iot_device_certificates';

    protected $fillable = [
        'iot_device_id',
        'tenant_id',
        'certificate_fingerprint',
        'public_key',
        'certificate_pem',
        'serial_number',
        'issuer',
        'issued_at',
        'expires_at',
        'is_revoked',
        'revoked_at',
        'revocation_reason',
        'key_algorithm',
        'key_bits',
        'signature_algorithm',
        'device_mac_address',
        'device_serial',
        'attestation_data',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
        'attestation_data' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(IoTDeviceModel::class, 'iot_device_id');
    }

    public function toDomain(): DeviceCertificateEntity
    {
        return new DeviceCertificateEntity(
            id: $this->id,
            iotDeviceId: $this->iot_device_id,
            tenantId: $this->tenant_id,
            certificateFingerprint: $this->certificate_fingerprint,
            publicKey: $this->public_key,
            certificatePem: $this->certificate_pem,
            serialNumber: $this->serial_number,
            issuer: $this->issuer,
            issuedAt: \Carbon\CarbonImmutable::parse($this->issued_at),
            expiresAt: \Carbon\CarbonImmutable::parse($this->expires_at),
            isRevoked: $this->is_revoked,
            revokedAt: $this->revoked_at ? \Carbon\CarbonImmutable::parse($this->revoked_at) : null,
            revocationReason: $this->revocation_reason,
            keyAlgorithm: $this->key_algorithm,
            keyBits: $this->key_bits,
            signatureAlgorithm: $this->signature_algorithm,
            deviceMacAddress: $this->device_mac_address,
            deviceSerial: $this->device_serial,
            attestationData: $this->attestation_data,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(DeviceCertificateEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'iot_device_id' => $entity->iotDeviceId,
            'tenant_id' => $entity->tenantId,
            'certificate_fingerprint' => $entity->certificateFingerprint,
            'public_key' => $entity->publicKey,
            'certificate_pem' => $entity->certificatePem,
            'serial_number' => $entity->serialNumber,
            'issuer' => $entity->issuer,
            'issued_at' => $entity->issuedAt,
            'expires_at' => $entity->expiresAt,
            'is_revoked' => $entity->isRevoked,
            'revoked_at' => $entity->revokedAt,
            'revocation_reason' => $entity->revocationReason,
            'key_algorithm' => $entity->keyAlgorithm,
            'key_bits' => $entity->keyBits,
            'signature_algorithm' => $entity->signatureAlgorithm,
            'device_mac_address' => $entity->deviceMacAddress,
            'device_serial' => $entity->deviceSerial,
            'attestation_data' => $entity->attestationData,
        ]);
    }
}
