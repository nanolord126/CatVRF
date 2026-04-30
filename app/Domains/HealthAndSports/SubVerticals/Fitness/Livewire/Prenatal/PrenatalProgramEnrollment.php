<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Prenatal;

use Livewire\Component;
use Modules\Fitness\Application\Services\PrenatalFitnessService;

class PrenatalProgramEnrollment extends Component
{
    public int $clientId;
    public array $availablePrograms = [];
    public ?int $activeEnrollmentId = null;
    
    public int $selectedProgramId = 0;
    public string $startDate = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(PrenatalFitnessService::class);
        
        $this->availablePrograms = $service->getActivePrenatalPrograms(auth()->user()->tenant_id);
    }

    public function enroll(): void
    {
        if ($this->selectedProgramId === 0) {
            session()->flash('error', 'Please select a program');
            return;
        }

        $service = app(PrenatalFitnessService::class);
        
        $service->enrollPrenatal(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            prenatalProgramId: $this->selectedProgramId,
            startDate: \Carbon\CarbonImmutable::parse($this->startDate),
        );

        $this->dispatch('enrollment-created');
        session()->flash('success', 'Enrolled successfully');
    }

    public function render()
    {
        return view('fitness::livewire.prenatal.program-enrollment');
    }
}
