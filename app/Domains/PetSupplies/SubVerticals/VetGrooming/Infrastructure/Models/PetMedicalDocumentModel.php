<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\PetMedicalDocument;
use Modules\VetGrooming\Domain\Enums\MedicalDocumentType;
use Modules\Media\Domain\Traits\HasMediaTrait;
use Carbon\CarbonImmutable;

class PetMedicalDocumentModel extends Model
{
    use SoftDeletes;
    use HasMediaTrait;

    protected $table = 'pet_medical_documents';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pet_id',
        'medical_record_id',
        'uploaded_by',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size_bytes',
        'description',
        'document_date',
        'is_encrypted',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'document_date' => 'date',
        'file_size_bytes' => 'integer',
        'is_encrypted' => 'boolean',
        'tags' => 'array',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pet::class, 'pet_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(\App\Models\VeterinaryMedicalRecord::class, 'medical_record_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function toDomain(): PetMedicalDocument
    {
        return new PetMedicalDocument(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            petId: $this->pet_id,
            medicalRecordId: $this->medical_record_id,
            uploadedBy: $this->uploaded_by,
            documentType: MedicalDocumentType::from($this->document_type),
            filePath: $this->file_path,
            fileName: $this->file_name,
            mimeType: $this->mime_type,
            fileSizeBytes: $this->file_size_bytes,
            description: $this->description,
            documentDate: $this->document_date ? CarbonImmutable::parse($this->document_date) : null,
            isEncrypted: $this->is_encrypted,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(PetMedicalDocument $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->pet_id = $entity->petId;
        $model->medical_record_id = $entity->medicalRecordId;
        $model->uploaded_by = $entity->uploadedBy;
        $model->document_type = $entity->documentType->value;
        $model->file_path = $entity->filePath;
        $model->file_name = $entity->fileName;
        $model->mime_type = $entity->mimeType;
        $model->file_size_bytes = $entity->fileSizeBytes;
        $model->description = $entity->description;
        $model->document_date = $entity->documentDate?->toDateString();
        $model->is_encrypted = $entity->isEncrypted;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
