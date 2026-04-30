<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\ExoticCertification;
use Modules\VetGrooming\Domain\Enums\CertificationLevel;
use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;

final class ExoticCertificationModel extends Model
{
    protected $table = 'exotic_certifications';

    protected $fillable = [
        'master_id',
        'tenant_id',
        'exotic_category',
        'subcategory',
        'exotic_group',
        'certification_level',
        'practical_exam_video',
        'issue_date',
        'expiry_date',
        'status',
        'certificate_number',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'expiry_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master::class, 'master_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function toDomain(): ExoticCertification
    {
        return new ExoticCertification(
            id: $this->id,
            masterId: $this->master_id,
            tenantId: $this->tenant_id,
            exoticCategory: ExoticCategory::from($this->exotic_category),
            subcategory: $this->subcategory,
            exoticGroup: ExoticGroup::from($this->exotic_group),
            certificationLevel: CertificationLevel::from($this->certification_level),
            practicalExamVideo: $this->practical_exam_video,
            issueDate: \Carbon\CarbonImmutable::parse($this->issue_date),
            expiryDate: \Carbon\CarbonImmutable::parse($this->expiry_date),
            status: $this->status,
            certificateNumber: $this->certificate_number,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(ExoticCertification $certification): self
    {
        return new self([
            'id' => $certification->id,
            'master_id' => $certification->masterId,
            'tenant_id' => $certification->tenantId,
            'exotic_category' => $certification->exoticCategory->value,
            'subcategory' => $certification->subcategory,
            'exotic_group' => $certification->exoticGroup->value,
            'certification_level' => $certification->certificationLevel->value,
            'practical_exam_video' => $certification->practicalExamVideo,
            'issue_date' => $certification->issueDate,
            'expiry_date' => $certification->expiryDate,
            'status' => $certification->status,
            'certificate_number' => $certification->certificateNumber,
            'metadata' => $certification->metadata,
            'created_at' => $certification->createdAt,
            'updated_at' => $certification->updatedAt,
        ]);
    }
}
