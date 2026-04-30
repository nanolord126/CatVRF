<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Modules\BeautyMasters\Infrastructure\Models\MasterModel;
use Modules\BeautyMasters\Domain\Entities\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class AdminCalendar extends Component
{
    use WithPagination;

    public int $venueId;
    public ?int $selectedMasterId = null;
    public ?int $selectedServiceId = null;
    public string $selectedStatus = 'all';
    public string $currentDate;
    public string $view = 'week'; // day, week, month
    public array $appointments = [];
    public array $masters = [];
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public ?int $editingAppointmentId = null;

    protected $listeners = [
        'appointmentCreated' => 'refreshCalendar',
        'appointmentUpdated' => 'refreshCalendar',
        'appointmentDeleted' => 'refreshCalendar',
        'refreshCalendar' => 'loadAppointments',
    ];

    public function mount(int $venueId): void
    {
        $this->venueId = $venueId;
        $this->currentDate = now()->format('Y-m-d');
        $this->loadMasters();
        $this->loadAppointments();
    }

    public function loadMasters(): void
    {
        $this->masters = MasterModel::where('venue_id', $this->venueId)
            ->where('is_active', true)
            ->get()
            ->map(fn ($master) => [
                'id' => $master->id,
                'name' => $master->full_name,
                'avatar' => $master->avatar,
            ])
            ->toArray();
    }

    public function loadAppointments(): void
    {
        $cacheKey = "beauty:admin_calendar:{$this->venueId}:{$this->selectedMasterId}:{$this->currentDate}:{$this->view}";

        $query = AppointmentModel::where('venue_id', $this->venueId)
            ->with(['master', 'client', 'service']);

        if ($this->selectedMasterId) {
            $query->where('master_id', $this->selectedMasterId);
        }

        if ($this->selectedServiceId) {
            $query->where('service_id', $this->selectedServiceId);
        }

        if ($this->selectedStatus !== 'all') {
            $query->where('status', $this->selectedStatus);
        }

        $dateRange = $this->getDateRange();
        $query->whereBetween('start_time', [$dateRange['start'], $dateRange['end']]);

        $this->appointments = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($query) {
            return $query
                ->orderBy('start_time')
                ->get()
                ->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'title' => $appointment->client->full_name,
                    'start' => $appointment->start_time->format('Y-m-d H:i:s'),
                    'end' => $appointment->end_time->format('Y-m-d H:i:s'),
                    'master_id' => $appointment->master_id,
                    'master_name' => $appointment->master->full_name,
                    'service_name' => $appointment->service->name,
                    'status' => $appointment->status,
                    'color' => $this->getStatusColor($appointment->status),
                    'price' => $appointment->final_price,
                ])
                ->toArray();
        });
    }

    private function getDateRange(): array
    {
        $date = Carbon::parse($this->currentDate);

        return match ($this->view) {
            'day' => [
                'start' => $date->startOfDay()->toDateTimeString(),
                'end' => $date->endOfDay()->toDateTimeString(),
            ],
            'week' => [
                'start' => $date->startOfWeek()->startOfDay()->toDateTimeString(),
                'end' => $date->endOfWeek()->endOfDay()->toDateTimeString(),
            ],
            'month' => [
                'start' => $date->startOfMonth()->startOfDay()->toDateTimeString(),
                'end' => $date->endOfMonth()->endOfDay()->toDateTimeString(),
            ],
            default => [
                'start' => $date->startOfDay()->toDateTimeString(),
                'end' => $date->endOfDay()->toDateTimeString(),
            ],
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            AppointmentStatus::PENDING => '#F59E0B', // yellow
            AppointmentStatus::CONFIRMED => '#3B82F6', // blue
            AppointmentStatus::IN_PROGRESS => '#F97316', // orange
            AppointmentStatus::COMPLETED => '#10B981', // green
            AppointmentStatus::CANCELLED => '#EF4444', // red
            AppointmentStatus::NO_SHOW => '#EF4444', // red
            AppointmentStatus::PAID => '#10B981', // green
            default => '#6B7280', // gray
        };
    }

    public function previousPeriod(): void
    {
        $date = Carbon::parse($this->currentDate);

        $this->currentDate = match ($this->view) {
            'day' => $date->subDay()->format('Y-m-d'),
            'week' => $date->subWeek()->format('Y-m-d'),
            'month' => $date->subMonth()->format('Y-m-d'),
            default => $date->subDay()->format('Y-m-d'),
        };

        $this->loadAppointments();
    }

    public function nextPeriod(): void
    {
        $date = Carbon::parse($this->currentDate);

        $this->currentDate = match ($this->view) {
            'day' => $date->addDay()->format('Y-m-d'),
            'week' => $date->addWeek()->format('Y-m-d'),
            'month' => $date->addMonth()->format('Y-m-d'),
            default => $date->addDay()->format('Y-m-d'),
        };

        $this->loadAppointments();
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadAppointments();
    }

    public function setView(string $view): void
    {
        $this->view = $view;
        $this->loadAppointments();
    }

    public function filterByMaster(?int $masterId): void
    {
        $this->selectedMasterId = $masterId;
        $this->loadAppointments();
    }

    public function filterByStatus(string $status): void
    {
        $this->selectedStatus = $status;
        $this->loadAppointments();
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function openEditModal(int $appointmentId): void
    {
        $this->editingAppointmentId = $appointmentId;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAppointmentId = null;
    }

    public function refreshCalendar(): void
    {
        $this->loadAppointments();
    }

    public function render()
    {
        return view('beauty-masters::livewire.admin-calendar', [
            'currentDateFormatted' => Carbon::parse($this->currentDate)->translatedFormat('F Y'),
            'dateRange' => $this->getDateRange(),
        ]);
    }
}
