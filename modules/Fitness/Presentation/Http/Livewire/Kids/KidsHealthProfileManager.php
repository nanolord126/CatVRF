<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Kids;

use Livewire\Component;
use Modules\Fitness\Application\Services\KidsFitnessService;
use Modules\Fitness\Domain\Kids\Entities\KidsHealthProfile;

class KidsHealthProfileManager extends Component
{
    public int $clientId;
    public ?KidsHealthProfile $profile = null;
    
    public array $formData = [
        'age_group' => 'school_6_8',
        'birth_date' => null,
        'parent_name' => null,
        'parent_phone' => null,
        'parent_email' => null,
        'has_allergies' => false,
        'allergies' => null,
        'has_asthma' => false,
        'has_heart_condition' => false,
        'medications' => null,
        'emergency_contact' => null,
        'emergency_phone' => null,
        'physical_limitations' => null,
    ];

    public function mount(int $clientId): void
    {
        $this->clientId = $clientId;
        $service = app(KidsFitnessService::class);
        $this->profile = $service->getHealthProfileByClientId($clientId);
        
        if ($this->profile) {
            $this->formData = [
                'age_group' => $this->profile->ageGroup,
                'birth_date' => $this->profile->birthDate?->format('Y-m-d'),
                'parent_name' => $this->profile->parentName,
                'parent_phone' => $this->profile->parentPhone,
                'parent_email' => $this->profile->parentEmail,
                'has_allergies' => $this->profile->hasAllergies,
                'allergies' => $this->profile->allergies,
                'has_asthma' => $this->profile->hasAsthma,
                'has_heart_condition' => $this->profile->hasHeartCondition,
                'medications' => $this->profile->medications,
                'emergency_contact' => $this->profile->emergencyContact,
                'emergency_phone' => $this->profile->emergencyPhone,
                'physical_limitations' => $this->profile->physicalLimitations,
            ];
        }
    }

    public function save(): void
    {
        $service = app(KidsFitnessService::class);
        
        $service->createHealthProfile(
            tenantId: auth()->user()->tenant_id,
            clientId: $this->clientId,
            ageGroup: $this->formData['age_group'],
            birthDate: $this->formData['birth_date'] ? \Carbon\CarbonImmutable::parse($this->formData['birth_date']) : null,
            parentName: $this->formData['parent_name'],
            parentPhone: $this->formData['parent_phone'],
            parentEmail: $this->formData['parent_email'],
            hasAllergies: $this->formData['has_allergies'],
            allergies: $this->formData['allergies'],
            hasAsthma: $this->formData['has_asthma'],
            hasHeartCondition: $this->formData['has_heart_condition'],
            medications: $this->formData['medications'],
            emergencyContact: $this->formData['emergency_contact'],
            emergencyPhone: $this->formData['emergency_phone'],
            physicalLimitations: $this->formData['physical_limitations'],
        );

        $this->dispatch('profile-saved');
        session()->flash('success', 'Health profile saved successfully');
    }

    public function render()
    {
        return view('fitness::livewire.kids.health-profile-manager');
    }
}
