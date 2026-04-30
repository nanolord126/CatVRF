<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Presentation\Livewire;

use Livewire\Component;
use Modules\VetGrooming\Application\Services\VaccinationScheduleService;
use Modules\VetGrooming\Domain\Repositories\PetVaccinationRepositoryInterface;
use Carbon\CarbonImmutable;
use Livewire\WithPagination;

/**
 * VetDashboard - Real-time Veterinary Dashboard
 * 
 * Shows for a specific veterinarian:
 * - Today's appointments
 * - Pets with overdue vaccinations
 * - Pets with critical conditions
 * - Quick vaccination schedule view
 * - Performance metrics
 */
class VetDashboard extends Component
{
    use WithPagination;

    public int $veterinarianId;
    public int $tenantId;
    public string $selectedDate;

    public array $todayAppointments = [];
    public array $overdueVaccinations = [];
    public array $criticalPatients = [];
    public array $stats = [];

    public function mount(int $veterinarianId, int $tenantId): void
    {
        $this->veterinarianId = $veterinarianId;
        $this->tenantId = $tenantId;
        $this->selectedDate = CarbonImmutable::now()->toDateString();

        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $vaccinationRepository = app(PetVaccinationRepositoryInterface::class);

        // Get overdue vaccinations for tenant
        $this->overdueVaccinations = $vaccinationRepository->findOverdueVaccinationsByTenant($this->tenantId, 10);

        // Get due vaccinations (within 30 days)
        $this->dueSoonVaccinations = $vaccinationRepository->findDueVaccinationsByTenant($this->tenantId, 10);

        // Calculate stats
        $this->stats = [
            'overdue_count' => count($this->overdueVaccinations),
            'due_soon_count' => count($this->dueSoonVaccinations),
            'today_appointments_count' => count($this->todayAppointments),
        ];
    }

    public function render()
    {
        return view('vetgrooming::livewire.vet-dashboard', [
            'veterinarianId' => $this->veterinarianId,
            'selectedDate' => $this->selectedDate,
            'overdueVaccinations' => $this->overdueVaccinations,
            'dueSoonVaccinations' => $this->dueSoonVaccinations ?? [],
            'stats' => $this->stats,
        ]);
    }
}
