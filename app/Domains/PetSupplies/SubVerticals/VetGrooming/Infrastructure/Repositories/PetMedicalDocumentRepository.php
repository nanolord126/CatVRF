<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Repositories\PetMedicalDocumentRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\PetMedicalDocument;
use Modules\VetGrooming\Domain\Enums\MedicalDocumentType;
use Modules\VetGrooming\Infrastructure\Models\PetMedicalDocumentModel;

class PetMedicalDocumentRepository implements PetMedicalDocumentRepositoryInterface
{
    public function findById(int $id): ?PetMedicalDocument
    {
        $model = PetMedicalDocumentModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?PetMedicalDocument
    {
        $model = PetMedicalDocumentModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = PetMedicalDocumentModel::where('pet_id', $petId)
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByPetIdAndType(int $petId, MedicalDocumentType $documentType): array
    {
        $models = PetMedicalDocumentModel::where('pet_id', $petId)
            ->where('document_type', $documentType->value)
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByMedicalRecordId(int $medicalRecordId): array
    {
        $models = PetMedicalDocumentModel::where('medical_record_id', $medicalRecordId)
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findLabResultsByPetId(int $petId): array
    {
        return $this->findByPetIdAndType($petId, MedicalDocumentType::LAB_RESULT);
    }

    public function findImagingByPetId(int $petId): array
    {
        $models = PetMedicalDocumentModel::where('pet_id', $petId)
            ->whereIn('document_type', [
                MedicalDocumentType::XRAY->value,
                MedicalDocumentType::ULTRASOUND->value,
                MedicalDocumentType::ECG->value,
                MedicalDocumentType::MRI->value,
                MedicalDocumentType::CT_SCAN->value,
            ])
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findCertificatesByPetId(int $petId): array
    {
        $models = PetMedicalDocumentModel::where('pet_id', $petId)
            ->whereIn('document_type', [
                MedicalDocumentType::PASSPORT->value,
                MedicalDocumentType::VACCINATION_CERTIFICATE->value,
            ])
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findDiagnosticByPetId(int $petId): array
    {
        $models = PetMedicalDocumentModel::where('pet_id', $petId)
            ->whereIn('document_type', [
                MedicalDocumentType::LAB_RESULT->value,
                MedicalDocumentType::XRAY->value,
                MedicalDocumentType::ULTRASOUND->value,
                MedicalDocumentType::ECG->value,
                MedicalDocumentType::MRI->value,
                MedicalDocumentType::CT_SCAN->value,
            ])
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByUploader(int $uploadedBy): array
    {
        $models = PetMedicalDocumentModel::where('uploaded_by', $uploadedBy)
            ->orderBy('document_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(PetMedicalDocument $document): PetMedicalDocument
    {
        $model = PetMedicalDocumentModel::fromDomain($document);
        $model->save();

        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        PetMedicalDocumentModel::destroy($id);
    }

    public function countByPetId(int $petId): int
    {
        return PetMedicalDocumentModel::where('pet_id', $petId)->count();
    }
}
