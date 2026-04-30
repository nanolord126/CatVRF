<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\VetGrooming\Domain\Enums\MedicalDocumentType;

final readonly class PetMedicalDocument
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $petId,
        public ?int $medicalRecordId,
        public ?int $uploadedBy,
        public MedicalDocumentType $documentType,
        public string $filePath,
        public string $fileName,
        public ?string $mimeType,
        public ?int $fileSizeBytes,
        public ?string $description,
        public ?CarbonImmutable $documentDate,
        public bool $isEncrypted,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $petId,
        MedicalDocumentType $documentType,
        string $filePath,
        string $fileName,
        ?int $medicalRecordId = null,
        ?int $uploadedBy = null,
        ?string $mimeType = null,
        ?int $fileSizeBytes = null,
        ?string $description = null,
        ?CarbonImmutable $documentDate = null,
        bool $isEncrypted = true,
        ?string $correlationId = null,
        ?array $tags = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            petId: $petId,
            medicalRecordId: $medicalRecordId,
            uploadedBy: $uploadedBy,
            documentType: $documentType,
            filePath: $filePath,
            fileName: $fileName,
            mimeType: $mimeType,
            fileSizeBytes: $fileSizeBytes,
            description: $description,
            documentDate: $documentDate ?? CarbonImmutable::now(),
            isEncrypted: $isEncrypted,
            correlationId: $correlationId,
            tags: $tags,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function isLabResult(): bool
    {
        return $this->documentType === MedicalDocumentType::LAB_RESULT;
    }

    public function isImaging(): bool
    {
        return in_array($this->documentType, [
            MedicalDocumentType::XRAY,
            MedicalDocumentType::ULTRASOUND,
            MedicalDocumentType::ECG,
            MedicalDocumentType::MRI,
            MedicalDocumentType::CT_SCAN,
        ]);
    }

    public function isCertificate(): bool
    {
        return in_array($this->documentType, [
            MedicalDocumentType::PASSPORT,
            MedicalDocumentType::VACCINATION_CERTIFICATE,
        ]);
    }

    public function getFileSizeInMB(): float
    {
        if ($this->fileSizeBytes === null) {
            return 0.0;
        }

        return round($this->fileSizeBytes / 1024 / 1024, 2);
    }
}
