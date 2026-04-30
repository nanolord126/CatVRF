<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Livewire;

use App\Domains\Fashion\Services\FashionAllergyService;
use Livewire\Component;

/**
 * MaterialAllergyChecker — Livewire component for checking material allergies.
 *
 * Healthcare compliance (152-ФЗ, ФЗ-323).
 * Shows context-aware allergy warnings for fashion products.
 *
 * @version 2026.1
 */
final class MaterialAllergyChecker extends Component
{
    public array $materials = [];

    public array $userAllergies = [];

    public array $safetyCheck = [];

    public bool $showWarning = false;

    public bool $isSafe = true;

    public function checkSafety(): void
    {
        $service = app(FashionAllergyService::class);
        $this->safetyCheck = $service->checkProductSafety($this->materials, $this->userAllergies);

        $this->isSafe = $this->safetyCheck['is_safe'];
        $this->showWarning = !$this->isSafe && $service->requiresAllergyWarning($this->materials, $this->userAllergies);
    }

    public function getSafeMaterials(): array
    {
        $service = app(FashionAllergyService::class);
        return $service->getSafeMaterials($this->userAllergies);
    }

    public function getUnsafeMaterials(): array
    {
        $service = app(FashionAllergyService::class);
        return $service->getUnsafeMaterials($this->userAllergies);
    }

    public function render()
    {
        return view('fashion.livewire.material-allergy-checker');
    }
}
