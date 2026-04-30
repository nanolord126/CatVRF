<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Prenatal;

use Livewire\Component;
use Modules\Fitness\Application\Services\PrenatalFitnessService;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalHealthProfile;

class PrenatalHealthProfileManager extends Component
{
    public int $clientId;
    public ?PrenatalHealthProfile $profile = null;
    
    public array $formData = [
        'due_date' => null,
        'trimester' => 'first',
        'has_high_risk_pregnancy' => false,
        'has_preeclampsia_risk' => false,
        'has_gestational_diabetes' => false,
        'obstetrician_notes' => null,
        'medications' => null,
        'allergies' => null,
        'emergency_contact' => null,
        'emergency_phone' => null,
        'physical_limitations' => null,
    ];

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(PrenatalFitnessService::class);
        $this->profile = $service->getHealthProfileByClientId($clientId);
        
        if ($this->profile) {
            $this->formData = [
                'due_date' => $this->profile->dueDate?->format('Y-m-d'),
                'trimester' => $this->profile->trimester,
                'has_high_risk_pregnancy' => $this->profile->hasHighRiskPregnancy,
                'has_preeclampsia_risk' => $this->profile->hasPreeclampsiaRisk,
                'has_gestational_diabetes' => $this->profile->hasGestationalDiabetes,
                'obstetrician_notes' => $this->profile->obstetricianNotes,
                'medications' => $this->profile->medications,
                'allergies' => $this->profile->allergies,
                'emergency_contact' => $this->profile->emergencyContact,
                'emergency_phone' => $this->profile->emergencyPhone,
                'physical_limitations' => $this->profile->physicalLimitations,
            ];
        }
    }

    public function save(): void
    {
        $service = app(PrenatalFitnessService::class);
        
        $service->createHealthProfile(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            dueDate: \Carbon\CarbonImmutable::parse($this->formData['due_date']),
            trimester: $this->formData['trimester'],
            hasHighRiskPregnancy: $this->formData['has_high_risk_pregnancy'],
            hasPreeclampsiaRisk: $this->formData['has_preeclampsia_risk'],
            hasGestationalDiabetes: $this->formData['has_gestational_diabetes'],
            obstetricianNotes: $this->formData['obstetrician_notes'],
            medications: $this->formData['medications'],
            allergies: $this->formData['allergies'],
            emergencyContact: $this->formData['emergency_contact'],
            emergencyPhone: $this->formData['emergency_phone'],
            physicalLimitations: $this->formData['physical_limitations'],
        );

        $this->dispatch('profile-saved');
        session()->flash('success', 'Health profile saved successfully');
    }

    public function render()
    {
        return view('fitness::livewire.prenatal.health-profile-manager');
    }
}
