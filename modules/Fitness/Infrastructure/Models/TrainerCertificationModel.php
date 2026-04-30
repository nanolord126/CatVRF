<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerCertification as TrainerCertificationEntity;

final class TrainerCertificationModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_certifications';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'uuid',
        'correlation_id',
        'certification_type',
        'name',
        'issuer',
        'issue_date',
        'expiry_date',
        'certificate_number',
        'document_file',
        'status',
        'is_verified',
        'verified_at',
        'verified_by',
        'notes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }
: HasMany
    public function specializations()
    {
        return $this->hasMany(TrainerSpecializationModel::class, 'certification_id');
    }
: HasMany
    public function testResults()
    {
        return $this->hasMany(CertificationTestResultModel::class, 'certification_id');
    }

    public function toDomain(): TrainerCertificationEntity
    {
        return new TrainerCertificationEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            certificationType: $this->certification_type,
            name: $this->name,
            issuer: $this->issuer,
            issueDate: \Carbon\CarbonImmutable::parse($this->issue_date),
            expiryDate: $this->expiry_date ? \Carbon\CarbonImmutable::parse($this->expiry_date) : null,
            certificateNumber: $this->certificate_number,
            documentFile: $this->document_file,
            status: $this->status,
            isVerified: $this->is_verified,
            verifiedAt: $this->verified_at ? \Carbon\CarbonImmutable::parse($this->verified_at) : null,
            verifiedBy: $this->verified_by,
            notes: $this->notes,
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerCertificationEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'certification_type' => $entity->certificationType,
            'name' => $entity->name,
            'issuer' => $entity->issuer,
            'issue_date' => $entity->issueDate,
            'expiry_date' => $entity->expiryDate,
            'certificate_number' => $entity->certificateNumber,
            'document_file' => $entity->documentFile,
            'status' => $entity->status,
            'is_verified' => $entity->isVerified,
            'verified_at' => $entity->verifiedAt,
            'verified_by' => $entity->verifiedBy,
            'notes' => $entity->notes,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
