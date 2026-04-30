<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Senior;

use Livewire\Component;
use Modules\Fitness\Application\Services\SeniorFitnessService;
use Modules\Fitness\Domain\Senior\Entities\SeniorHealthProfile;

class SeniorHealthProfileManager extends Component
{
    public int $clientId;
    public ?SeniorHealthProfile $profile = null;
    
    public array $formData = [
        'birth_date' => null,
        'has_diabetes' => false,
        'has_heart_condition' => false,
        'has_hypertension' => false,
        'has_arthritis' => false,
        'has_osteoporosis' => false,
        'has_balance_issues' => false,
        'medications' => null,
        'allergies' => null,
        'emergency_contact' => null,
        'emergency_phone' => null,
        'physical_limitations' => null,
    ];

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(SeniorFitnessService::class);
        $this->profile = $service->getHealthProfileByClientId($clientId);
        
        if ($this->profile) {
            $this->formData = [
                'birth_date' => $this->profile->birthDate?->format('Y-m-d'),
                'has_diabetes' => $this->profile->hasDiabetes,
                'has_heart_condition' => $this->profile->hasHeartCondition,
                'has_hypertension' => $this->profile->hasHypertension,
                'has_arthritis' => $this->profile->hasArthritis,
                'has_osteoporosis' => $this->profile->hasOsteoporosis,
                'has_balance_issues' => $this->profile->hasBalanceIssues,
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
        $service = app(SeniorFitnessService::class);
        
        $service->createHealthProfile(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            birthDate: $this->formData['birth_date'] ? \Carbon\CarbonImmutable::parse($this->formData['birth_date']) : null,
            hasDiabetes: $this->formData['has_diabetes'],
            hasHeartCondition: $this->formData['has_heart_condition'],
            hasHypertension: $this->formData['has_hypertension'],
            hasArthritis: $this->formData['has_arthritis'],
            hasOsteoporosis: $this->formData['has_osteoporosis'],
            hasBalanceIssues: $this->formData['has_balance_issues'],
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
        return view('fitness::livewire.senior.health-profile-manager');
    }
}
