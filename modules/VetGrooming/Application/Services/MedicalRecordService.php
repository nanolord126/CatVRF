<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\VetGrooming\Domain\Repositories\PetChronicConditionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\PetMedicalDocumentRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\PetChronicCondition;
use Modules\VetGrooming\Domain\Entities\PetMedicalDocument;
use Modules\VetGrooming\Domain\Enums\ConditionType;
use Modules\VetGrooming\Domain\Enums\MedicalDocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * MedicalRecordService - Electronic Medical Card Management
 * 
 * Handles medical records, chronic conditions, allergies, and document attachments
 * with compliance to Russian veterinary legislation (152-ФЗ, ФЗ-323).
 */
final class MedicalRecordService
{
    use WithAuditLogging;

    public function __construct(
        private readonly PetChronicConditionRepositoryInterface $conditionRepository,
        private readonly PetMedicalDocumentRepositoryInterface $documentRepository,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a new chronic condition or allergy record
     */
    public function createCondition(
        int $tenantId,
        int $petId,
        ConditionType $conditionType,
        string $conditionName,
        ?int $veterinarianId = null,
        ?string $icdCode = null,
        ?CarbonImmutable $diagnosedDate = null,
        ?string $description = null,
        ?string $severity = null,
        ?array $symptoms = null,
        ?array $triggers = null,
        ?array $managementNotes = null,
    ): PetChronicCondition {
        $condition = PetChronicCondition::create(
            tenantId: $tenantId,
            petId: $petId,
            conditionType: $conditionType,
            conditionName: $conditionName,
            veterinarianId: $veterinarianId,
            icdCode: $icdCode,
            diagnosedDate: $diagnosedDate,
            description: $description,
            severity: $severity,
            symptoms: $symptoms,
            triggers: $triggers,
            managementNotes: $managementNotes,
        );

        return $this->conditionRepository->save($condition);
    }

    /**
     * Resolve a chronic condition (mark as inactive)
     */
    public function resolveCondition(int $conditionId, ?string $notes = null): PetChronicCondition
    {
        $condition = $this->conditionRepository->findById($conditionId);
        if (! $condition) {
            throw new \InvalidArgumentException("Condition not found: {$conditionId}");
        }

        $resolved = $condition->resolve($notes);
        return $this->conditionRepository->save($resolved);
    }

    /**
     * Reactivate a resolved condition
     */
    public function reactivateCondition(int $conditionId): PetChronicCondition
    {
        $condition = $this->conditionRepository->findById($conditionId);
        if (! $condition) {
            throw new \InvalidArgumentException("Condition not found: {$conditionId}");
        }

        $reactivated = $condition->reactivate();
        return $this->conditionRepository->save($reactivated);
    }

    /**
     * Get quick medical summary for vet dashboard (30-second view)
     */
    public function getQuickSummary(int $petId): array
    {
        $activeConditions = $this->conditionRepository->findActiveByPetId($petId);
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $criticalConditions = $this->conditionRepository->findCriticalConditionsByPetId($petId);
        $anesthesiaIntolerances = $this->conditionRepository->findAnesthesiaIntolerancesByPetId($petId);

        return [
            'active_conditions_count' => count($activeConditions),
            'allergies_count' => count($allergies),
            'has_critical_conditions' => count($criticalConditions) > 0,
            'has_anesthesia_intolerance' => count($anesthesiaIntolerances) > 0,
            'allergies' => array_map(fn ($a) => [
                'name' => $a->conditionName,
                'type' => $a->conditionType->value,
                'severity' => $a->severity,
                'triggers' => $a->triggers,
            ], $allergies),
            'critical_conditions' => array_map(fn ($c) => [
                'name' => $c->conditionName,
                'severity' => $c->severity,
                'notes' => $c->managementNotes,
            ], $criticalConditions),
            'anesthesia_warnings' => array_map(fn ($a) => [
                'condition' => $a->conditionName,
                'notes' => $a->description,
            ], $anesthesiaIntolerances),
        ];
    }

    /**
     * Upload and attach a medical document
     */
    public function attachDocument(
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
    ): PetMedicalDocument {
        $document = PetMedicalDocument::create(
            tenantId: $tenantId,
            petId: $petId,
            documentType: $documentType,
            filePath: $filePath,
            fileName: $fileName,
            medicalRecordId: $medicalRecordId,
            uploadedBy: $uploadedBy,
            mimeType: $mimeType,
            fileSizeBytes: $fileSizeBytes,
            description: $description,
            documentDate: $documentDate,
            isEncrypted: $isEncrypted,
        );

        return $this->documentRepository->save($document);
    }

    /**
     * Get medical documents by type
     */
    public function getDocumentsByType(int $petId, MedicalDocumentType $documentType): array
    {
        return $this->documentRepository->findByPetIdAndType($petId, $documentType);
    }

    /**
     * Get all diagnostic documents (lab results, imaging)
     */
    public function getDiagnosticDocuments(int $petId): array
    {
        return $this->documentRepository->findDiagnosticByPetId($petId);
    }

    /**
     * Get vaccination certificates
     */
    public function getVaccinationCertificates(int $petId): array
    {
        $certificates = $this->documentRepository->findByPetIdAndType(
            $petId,
            MedicalDocumentType::VACCINATION_CERTIFICATE
        );

        return array_map(fn ($doc) => [
            'id' => $doc->id,
            'file_name' => $doc->fileName,
            'document_date' => $doc->documentDate?->toDateString(),
            'file_path' => $doc->filePath,
        ], $certificates);
    }

    /**
     * Check for allergies before administering medications
     */
    public function checkMedicationAllergies(int $petId, array $medications): array
    {
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $warnings = [];

        foreach ($allergies as $allergy) {
            if ($allergy->conditionType === ConditionType::ALLERGY_MEDICATION) {
                foreach ($medications as $medication) {
                    if ($this->isMedicationAllergenic($medication, $allergy->conditionName, $allergy->triggers)) {
                        $warnings[] = [
                            'medication' => $medication,
                            'allergen' => $allergy->conditionName,
                            'severity' => $allergy->severity,
                            'notes' => $allergy->description,
                            'is_critical' => $allergy->isCritical(),
                        ];
                    }
                }
            }
        }

        return $warnings;
    }

    /**
     * Check if medication is allergenic based on condition data
     */
    private function isMedicationAllergenic(string $medication, string $allergen, ?array $triggers): bool
    {
        $medicationLower = strtolower($medication);
        $allergenLower = strtolower($allergen);

        // Direct match
        if (str_contains($medicationLower, $allergenLower)) {
            return true;
        }

        // Check triggers if available
        if ($triggers) {
            foreach ($triggers as $trigger) {
                if (str_contains($medicationLower, strtolower($trigger))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Export medical card summary (for sharing with other clinics)
     */
    public function exportMedicalSummary(int $petId): array
    {
        $activeConditions = $this->conditionRepository->findActiveByPetId($petId);
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $criticalConditions = $this->conditionRepository->findCriticalConditionsByPetId($petId);
        $documents = $this->documentRepository->findDiagnosticByPetId($petId);

        return [
            'export_date' => CarbonImmutable::now()->toIso8601String(),
            'pet_id' => $petId,
            'active_conditions' => array_map(fn ($c) => [
                'condition' => $c->conditionName,
                'type' => $c->conditionType->value,
                'icd_code' => $c->icdCode,
                'diagnosed_date' => $c->diagnosedDate?->toDateString(),
                'severity' => $c->severity,
                'notes' => $c->description,
            ], $activeConditions),
            'allergies' => array_map(fn ($a) => [
                'allergen' => $a->conditionName,
                'type' => $a->conditionType->value,
                'severity' => $a->severity,
                'triggers' => $a->triggers,
            ], $allergies),
            'critical_alerts' => array_map(fn ($c) => [
                'condition' => $c->conditionName,
                'severity' => $c->severity,
                'management' => $c->managementNotes,
            ], $criticalConditions),
            'recent_documents_count' => count($documents),
        ];
    }

    /**
     * Get grooming-relevant medical information (for groomers)
     */
    public function getGroomingMedicalInfo(int $petId): array
    {
        $allergies = $this->conditionRepository->findActiveAllergiesByPetId($petId);
        $skinConditions = $this->conditionRepository->findActiveByPetIdAndConditionType(
            $petId,
            ConditionType::CHRONIC_DISEASE
        );

        // Filter for skin-relevant conditions
        $skinRelevant = array_filter($skinConditions, fn ($c) => 
            str_contains(strtolower($c->conditionName), 'кожа') ||
            str_contains(strtolower($c->conditionName), 'skin') ||
            str_contains(strtolower($c->description ?? ''), 'кожа') ||
            str_contains(strtolower($c->description ?? ''), 'skin')
        );

        return [
            'has_allergies' => count($allergies) > 0,
            'allergies' => array_map(fn ($a) => [
                'allergen' => $a->conditionName,
                'type' => $a->conditionType->value,
                'triggers' => $a->triggers,
                'notes' => $a->description,
            ], $allergies),
            'skin_conditions' => array_map(fn ($c) => [
                'condition' => $c->conditionName,
                'notes' => $c->description,
                'management' => $c->managementNotes,
            ], $skinRelevant),
            'requires_special_handling' => count($allergies) > 0 || count($skinRelevant) > 0,
        ];
    }
}
