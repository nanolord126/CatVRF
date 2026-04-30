<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Senior;

use Livewire\Component;
use Modules\Fitness\Application\Services\SeniorFitnessService;
use Modules\Fitness\Domain\Senior\Entities\SeniorProgramEnrollment;

class SeniorProgramEnrollment extends Component
{
    public int $clientId;
    public array $availablePrograms = [];
    public ?SeniorProgramEnrollment $activeEnrollment = null;
    
    public int $selectedProgramId = 0;
    public string $startDate = '';

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(SeniorFitnessService::class);
        
        $this->availablePrograms = $service->getActiveSeniorPrograms(auth()->user()->tenant_id);
        $activeEnrollments = $service->getActiveEnrollmentsByClientId($clientId);
        $this->activeEnrollment = $activeEnrollments[0] ?? null;
    }

    public function enroll(): void
    {
        if ($this->selectedProgramId === 0) {
            session()->flash('error', 'Please select a program');
            return;
        }

        $service = app(SeniorFitnessService::class);
        
        $service->enrollSenior(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            seniorProgramId: $this->selectedProgramId,
            startDate: \Carbon\CarbonImmutable::parse($this->startDate),
        );

        $this->dispatch('enrollment-created');
        session()->flash('success', 'Enrolled successfully');
        
        $this->mount($this->clientId);
    }

    public function render()
    {
        return view('fitness::livewire.senior.program-enrollment');
    }
}
