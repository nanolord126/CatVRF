<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Kids;

use Livewire\Component;
use Modules\Fitness\Application\Services\KidsFitnessService;

class KidsProgramEnrollment extends Component
{
    public int $clientId;
    public array $availablePrograms = [];
    public ?int $activeEnrollmentId = null;
    
    public int $selectedProgramId = 0;
    public string $startDate = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(KidsFitnessService::class);
        
        $this->availablePrograms = $service->getActiveKidsPrograms(auth()->user()->tenant_id);
    }

    public function enroll(): void
    {
        if ($this->selectedProgramId === 0) {
            session()->flash('error', 'Please select a program');
            return;
        }

        $service = app(KidsFitnessService::class);
        
        $service->enrollKids(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            kidsProgramId: $this->selectedProgramId,
            startDate: \Carbon\CarbonImmutable::parse($this->startDate),
        );

        $this->dispatch('enrollment-created');
        session()->flash('success', 'Enrolled successfully');
    }

    public function render()
    {
        return view('fitness::livewire.kids.program-enrollment');
    }
}
