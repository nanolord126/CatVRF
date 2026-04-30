<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Presentation\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\VaccinationScheduleService;
use Modules\VetGrooming\Domain\Repositories\PetVaccinationRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * VaccinationTimeline - Visual Vaccination History and Schedule
 * 
 * Shows:
 * - Timeline of past vaccinations
 * - Upcoming vaccinations with color coding
 * - WSAVA 2024 compliance indicators
 * - Easy one-click scheduling
 */
class VaccinationTimeline extends Component
{
    public int $petId;
    public string $species;
    public ?string $birthDate;
    
    public array $schedule = [];
    public array $history = [];
    public array $upcoming = [];
    public array $overdue = [];

    public function mount(int $petId, string $species, ?string $birthDate): void
    {
        $this->petId = $petId;
        $this->species = $species;
        $this->birthDate = $birthDate;

        $this->loadData();
    }

    public function loadData(): void
    {
        $vaccinationService = app(VaccinationScheduleService::class);
        $vaccinationRepository = app(PetVaccinationRepositoryInterface::class);

        // Generate schedule based on WSAVA 2024
        if ($this->birthDate) {
            $this->schedule = $vaccinationService->generateSchedule(
                petId: $this->petId,
                species: $this->species,
                birthDate: CarbonImmutable::parse($this->birthDate),
                riskFactors: [] // Would load from pet profile
            );
        }

        // Get vaccination history
        $this->history = $vaccinationRepository->findByPetId($this->petId);

        // Get upcoming vaccinations
        $dueInfo = $vaccinationService->getNextDueVaccinations($this->petId);
        $this->upcoming = $dueInfo['due_soon'];
        $this->overdue = $dueInfo['overdue'];
    }

    public function render()
    {
        return view('vetgrooming::livewire.vaccination-timeline', [
            'schedule' => $this->schedule,
            'history' => $this->history,
            'upcoming' => $this->upcoming,
            'overdue' => $this->overdue,
        ]);
    }
}
