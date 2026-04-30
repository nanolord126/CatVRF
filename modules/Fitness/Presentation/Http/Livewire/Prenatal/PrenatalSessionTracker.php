<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Prenatal;

use Livewire\Component;
use Modules\Fitness\Application\Services\PrenatalFitnessService;

class PrenatalSessionTracker extends Component
{
    public int $enrollmentId;
    public array $sessions = [];
    
    public array $formData = [
        'session_date' => '',
        'exercise_type' => '',
        'duration_minutes' => 30,
        'heart_rate_before' => null,
        'heart_rate_after' => null,
        'blood_pressure_systolic' => null,
        'blood_pressure_diastolic' => null,
        'notes' => '',
    ];

    public function mount(int $enrollmentId): void
    {
        $this->enrollmentId = $enrollmentId;
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $service = app(PrenatalFitnessService::class);
        $this->sessions = $service->getSessionLogs($this->enrollmentId);
    }

    public function logSession(): void
    {
        $service = app(PrenatalFitnessService::class);
        
        $service->logSession(
            tenantId: auth()->user()->tenant_id,
            enrollmentId: $this->enrollmentId,
            sessionDate: \Carbon\CarbonImmutable::parse($this->formData['session_date']),
            exerciseType: $this->formData['exercise_type'],
            durationMinutes: (int) $this->formData['duration_minutes'],
            heartRateBefore: $this->formData['heart_rate_before'] ? (int) $this->formData['heart_rate_before'] : null,
            heartRateAfter: $this->formData['heart_rate_after'] ? (int) $this->formData['heart_rate_after'] : null,
            bloodPressureSystolic: $this->formData['blood_pressure_systolic'] ? (int) $this->formData['blood_pressure_systolic'] : null,
            bloodPressureDiastolic: $this->formData['blood_pressure_diastolic'] ? (int) $this->formData['blood_pressure_diastolic'] : null,
            notes: $this->formData['notes'],
        );

        $this->reset('formData');
        $this->formData['duration_minutes'] = 30;
        $this->loadSessions();
        
        $this->dispatch('session-logged');
        session()->flash('success', 'Session logged successfully');
    }

    public function render()
    {
        return view('fitness::livewire.prenatal.session-tracker');
    }
}
