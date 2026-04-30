<?php

declare(strict_types=1);

namespace Modules\Fitness\Livewire\Kids;

use Livewire\Component;
use Modules\Fitness\Application\Services\KidsFitnessService;

class KidsSessionTracker extends Component
{
    public int $enrollmentId;
    public array $sessions = [];
    
    public array $formData = [
        'session_date' => '',
        'activity_type' => '',
        'duration_minutes' => 30,
        'mood' => 'happy',
        'notes' => '',
    ];

    public function mount(int $enrollmentId): void
    {
        $this->enrollmentId = $enrollmentId;
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $service = app(KidsFitnessService::class);
        $this->sessions = $service->getSessionLogs($this->enrollmentId);
    }

    public function logSession(): void
    {
        $service = app(KidsFitnessService::class);
        
        $service->logSession(
            tenantId: auth()->user()->tenant_id,
            enrollmentId: $this->enrollmentId,
            sessionDate: \Carbon\CarbonImmutable::parse($this->formData['session_date']),
            activityType: $this->formData['activity_type'],
            durationMinutes: (int) $this->formData['duration_minutes'],
            mood: $this->formData['mood'],
            notes: $this->formData['notes'],
        );

        $this->reset('formData');
        $this->formData['duration_minutes'] = 30;
        $this->formData['mood'] = 'happy';
        $this->loadSessions();
        
        $this->dispatch('session-logged');
        session()->flash('success', 'Session logged successfully');
    }

    public function render()
    {
        return view('fitness::livewire.kids.session-tracker');
    }
}
