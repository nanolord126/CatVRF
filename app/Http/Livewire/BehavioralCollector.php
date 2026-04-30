<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * Behavioral Collector Livewire Component
 *
 * Integrates the JavaScript behavioral collector with Livewire.
 * Handles signal collection and batch sending to backend.
 *
 * Usage in Blade:
 * <livewire:behavioral-collector action="login" />
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class BehavioralCollector extends Component
{
    public string $action = 'general';
    public bool $enabled = true;
    public bool $collecting = false;

    protected $listeners = [
        'behavioralSignalsCollected' => 'handleSignalsCollected',
    ];

    public function mount(string $action = 'general'): void
    {
        $this->action = $action;
        $this->enabled = config('behavioral.enabled', true);
        
        if (! $this->enabled) {
            return;
        }

        // Check user consent
        $user = Auth::user();
        if ($user && isset($user->behavioral_consent)) {
            $this->enabled = (bool) $user->behavioral_consent;
        }
    }

    public function startCollection(): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->collecting = true;
        $this->dispatch('startBehavioralCollection', [
            'action' => $this->action,
            'throttleMs' => config('behavioral.collection.throttle_ms', 50),
            'batchIntervalMs' => config('behavioral.collection.batch_interval_ms', 30000),
        ]);
    }

    public function stopCollection(): void
    {
        $this->collecting = false;
        $this->dispatch('stopBehavioralCollection');
    }

    public function collectNow(): void
    {
        if (! $this->enabled || ! $this->collecting) {
            return;
        }

        $this->dispatch('collectBehavioralSignals', [
            'action' => $this->action,
        ]);
    }

    public function handleSignalsCollected(array $data): void
    {
        // Handle successful collection if needed
        // Currently handled by direct API call from JS
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.behavioral-collector');
    }
}
