<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\PetMedicalDocument;
use Modules\VetGrooming\Domain\Enums\MedicalDocumentType;

interface PetMedicalDocumentRepositoryInterface
{
    public function findById(int $id): ?PetMedicalDocument;

    public function findByUuid(string $uuid): ?PetMedicalDocument;

    public function findByPetId(int $petId): array;

    public function findByPetIdAndType(int $petId, MedicalDocumentType $documentType): array;

    public function findByMedicalRecordId(int $medicalRecordId): array;

    public function findLabResultsByPetId(int $petId): array;

    public function findImagingByPetId(int $petId): array;

    public function findCertificatesByPetId(int $petId): array;

    public function findDiagnosticByPetId(int $petId): array;

    public function findByUploader(int $uploadedBy): array;

    public function save(PetMedicalDocument $document): PetMedicalDocument;

    public function delete(int $id): void;

    public function countByPetId(int $petId): int;
}
