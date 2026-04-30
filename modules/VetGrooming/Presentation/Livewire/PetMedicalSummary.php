<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Presentation\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\VaccinationScheduleService;
use Modules\VetGrooming\Application\Services\MedicalRecordService;
use Livewire\Attributes\Reactive;
use Livewire\WithPagination;

/**
 * PetMedicalSummary - 30-Second Medical Overview for Vets
 * 
 * Shows critical medical information at a glance:
 * - Age, weight, species
 * - Active allergies (highlighted red)
 * - Chronic conditions
 * - Last vaccinations
 * - Upcoming vaccinations
 * - Anesthesia intolerances
 */
class PetMedicalSummary extends Component
{
    use WithPagination;

    public int $petId;
    public string $species;
    public string $breed;
    public ?string $birthDate;
    public ?float $weight;

    #[Reactive]
    public bool $refresh = false;

    public function mount(int $petId): void
    {
        $this->petId = $petId;
    }

    public function render()
    {
        $vaccinationService = app(VaccinationScheduleService::class);
        $medicalService = app(MedicalRecordService::class);

        $vaccinationSummary = $vaccinationService->getQuickSummary($this->petId);
        $medicalSummary = $medicalService->getQuickSummary($this->petId);

        return view('vetgrooming::livewire.pet-medical-summary', [
            'petId' => $this->petId,
            'species' => $this->species,
            'breed' => $this->breed,
            'birthDate' => $this->birthDate,
            'weight' => $this->weight,
            'vaccinationSummary' => $vaccinationSummary,
            'medicalSummary' => $medicalSummary,
        ]);
    }
}
