<?php

declare(strict_types=1);

namespace App\Domains\Shared\Contraindications\Entities;

use App\Domains\Shared\Contraindications\ValueObjects\AllergenType;
use App\Domains\Shared\Contraindications\ValueObjects\SeverityLevel;

final readonly class MaterialAllergy
{
    private function __construct(
        private string $uuid,
        private string $materialName,
        private AllergenType $allergenType,
        private SeverityLevel $severity,
        private array $triggeringMaterials,
        private string $description,
        private ?string $medicalReference,
        private bool $isActive,
    ) {}

    public static function create(
        string $materialName,
        AllergenType $allergenType,
        SeverityLevel $severity,
        array $triggeringMaterials,
        string $description,
        ?string $medicalReference = null,
    ): self {
        return new self(
            uuid: (string) \Illuminate\Support\Str::uuid(),
            materialName: $materialName,
            allergenType: $allergenType,
            severity: $severity,
            triggeringMaterials: $triggeringMaterials,
            description: $description,
            medicalReference: $medicalReference,
            isActive: true,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            materialName: $data['material_name'],
            allergenType: AllergenType::from($data['allergen_type']),
            severity: SeverityLevel::from($data['severity']),
            triggeringMaterials: $data['triggering_materials'],
            description: $data['description'],
            medicalReference: $data['medical_reference'] ?? null,
            isActive: $data['is_active'],
        );
    }

    public function deactivate(): self
    {
        return new self(
            uuid: $this->uuid,
            materialName: $this->materialName,
            allergenType: $this->allergenType,
            severity: $this->severity,
            triggeringMaterials: $this->triggeringMaterials,
            description: $this->description,
            medicalReference: $this->medicalReference,
            isActive: false,
        );
    }

    public function isRelevantForMaterial(string $material): bool
    {
        if (!$this->isActive) {
            return false;
        }

        // Direct match
        if (in_array(strtolower($material), array_map('strtolower', $this->triggeringMaterials))) {
            return true;
        }

        // Partial match for compound materials
        foreach ($this->triggeringMaterials as $triggerMaterial) {
            if (str_contains(strtolower($material), strtolower($triggerMaterial))) {
                return true;
            }
        }

        return false;
    }

    public function isRelevantForCategory(string $category): bool
    {
        // Context-aware relevance - only show relevant allergies for category
        $categoryMappings = [
            'footwear' => ['leather', 'rubber', 'latex', 'glue', 'chromium'],
            'fashion' => ['wool', 'cotton', 'polyester', 'latex', 'nickel', 'formaldehyde'],
            'accessories' => ['nickel', 'latex', 'leather'],
        ];

        if (!isset($categoryMappings[$category])) {
            return true; // Show all if category unknown
        }

        $relevantMaterials = $categoryMappings[$category];
        foreach ($this->triggeringMaterials as $triggerMaterial) {
            if (in_array(strtolower($triggerMaterial), $relevantMaterials)) {
                return true;
            }
        }

        return false;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMaterialName(): string
    {
        return $this->materialName;
    }

    public function getAllergenType(): AllergenType
    {
        return $this->allergenType;
    }

    public function getSeverity(): SeverityLevel
    {
        return $this->severity;
    }

    public function getTriggeringMaterials(): array
    {
        return $this->triggeringMaterials;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMedicalReference(): ?string
    {
        return $this->medicalReference;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'material_name' => $this->materialName,
            'allergen_type' => $this->allergenType->value,
            'severity' => $this->severity->value,
            'triggering_materials' => $this->triggeringMaterials,
            'description' => $this->description,
            'medical_reference' => $this->medicalReference,
            'is_active' => $this->isActive,
        ];
    }
}
