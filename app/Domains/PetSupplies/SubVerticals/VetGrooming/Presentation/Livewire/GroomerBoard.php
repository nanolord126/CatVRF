<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Presentation\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\GroomingService;
use Carbon\CarbonImmutable;
use Livewire\WithPagination;

/**
 * GroomerBoard - Real-time Groomer Dashboard
 * 
 * Shows for a specific groomer:
 * - Today's sessions
 * - Upcoming sessions
 * - Pets with allergy alerts
 * - Performance metrics
 * - Photo upload capability
 */
class GroomerBoard extends Component
{
    use WithPagination;

    public int $groomerId;
    public int $tenantId;
    public string $selectedDate;

    public array $todaySessions = [];
    public array $upcomingSessions = [];
    public array $allergyAlerts = [];
    public array $stats = [];

    public function mount(int $groomerId, int $tenantId): void
    {
        $this->groomerId = $groomerId;
        $this->tenantId = $tenantId;
        $this->selectedDate = CarbonImmutable::now()->toDateString();

        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $groomingService = app(GroomingService::class);

        // Get today's schedule
        $this->todaySessions = $groomingService->getGroomerSchedule(
            $this->groomerId,
            CarbonImmutable::parse($this->selectedDate)
        );

        // Get aggressive behavior alerts for tenant
        $this->allergyAlerts = $groomingService->getAggressiveBehaviorAlerts($this->tenantId);

        // Get stats for this month
        $startDate = CarbonImmutable::now()->startOfMonth();
        $endDate = CarbonImmutable::now()->endOfMonth();
        $this->stats = $groomingService->getGroomerStats($this->groomerId, $startDate, $endDate);
    }

    public function startSession(int $sessionId): void
    {
        $groomingService = app(GroomingService::class);
        try {
            $groomingService->startSession($sessionId, $this->groomerId);
            $this->loadDashboardData();
            $this->dispatch('session-started');
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('vetgrooming::livewire.groomer-board', [
            'groomerId' => $this->groomerId,
            'selectedDate' => $this->selectedDate,
            'todaySessions' => $this->todaySessions,
            'allergyAlerts' => $this->allergyAlerts,
            'stats' => $this->stats,
        ]);
    }
}
