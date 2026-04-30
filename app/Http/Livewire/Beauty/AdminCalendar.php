<?php

declare(strict_types=1);

namespace App\Http\Livewire\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\MasterSchedule;
use App\Domains\Beauty\Models\BlockedSlot;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * AdminCalendar — главный календарь для администратора/владельца.
 * 
 * Показывает всех мастеров одновременно с фильтрами.
 * Drag & Drop для перемещения записей.
 * Цветовая индикация по статусу записи.
 */
final class AdminCalendar extends Component
{
    public string $currentDate = '';

    public string $viewMode = 'week'; // day, week, month

    public ?int $selectedMasterId = null;

    public ?int $selectedServiceId = null;

    public ?string $selectedStatus = null;

    public array $appointments = [];

    public array $masters = [];

    public array $blockedSlots = [];

    public bool $showCreateModal = false;

    public array $newAppointment = [
        'master_id' => null,
        'service_id' => null,
        'date' => null,
        'time' => null,
    ];

    public function __construct(private readonly Factory $viewFactory) {}

    public function mount(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData(): void
    {
        $date = Carbon::parse($this->currentDate);
        
        // Load masters
        $this->masters = Master::where('tenant_id', tenant()->id ?? 1)
            ->with(['salon'])
            ->get()
            ->map(fn ($master) => [
                'id' => $master->id,
                'name' => $master->full_name,
                'salon_name' => $master->salon->name ?? 'N/A',
            ])
            ->toArray();

        // Load appointments with filters
        $query = Appointment::where('tenant_id', tenant()->id ?? 1)
            ->with(['master', 'service', 'salon'])
            ->whereBetween('starts_at', [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ]);

        if ($this->selectedMasterId) {
            $query->where('master_id', $this->selectedMasterId);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        $appointments = $query->get();

        $this->appointments = $appointments->map(fn ($appointment) => [
            'id' => $appointment->id,
            'title' => $appointment->service->name ?? 'Услуга',
            'master_id' => $appointment->master_id,
            'master_name' => $appointment->master->full_name ?? 'N/A',
            'start' => $appointment->starts_at->toIso8601String(),
            'end' => $appointment->ends_at->toIso8601String(),
            'status' => $appointment->status,
            'color' => $this->getStatusColor($appointment->status),
        ])->toArray();

        // Load blocked slots
        $this->blockedSlots = BlockedSlot::where('tenant_id', tenant()->id ?? 1)
            ->whereBetween('start_time', [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ])
            ->when($this->selectedMasterId, fn ($q) => $q->where('master_id', $this->selectedMasterId))
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'master_id' => $slot->master_id,
                'start' => $slot->start_time->toIso8601String(),
                'end' => $slot->end_time->toIso8601String(),
                'reason' => $slot->reason,
                'notes' => $slot->notes,
            ])
            ->toArray();
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'in_progress' => 'primary',
            'completed' => 'success',
            'cancelled' => 'danger',
            'no_show' => 'secondary',
            default => 'secondary',
        };
    }

    public function previousWeek(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)
            ->subWeek()
            ->format('Y-m-d');
        $this->loadData();
    }

    public function nextWeek(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)
            ->addWeek()
            ->format('Y-m-d');
        $this->loadData();
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function createAppointment(): void
    {
        $this->validate([
            'newAppointment.master_id' => 'required|integer|exists:beauty_masters,id',
            'newAppointment.service_id' => 'required|integer|exists:beauty_services,id',
            'newAppointment.date' => 'required|date',
            'newAppointment.time' => 'required|date_format:H:i',
        ]);

        // Logic to create appointment would go here
        // This would call AppointmentService

        $this->showCreateModal = false;
        $this->loadData();
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.beauty.admin-calendar', [
            'currentDate' => Carbon::parse($this->currentDate),
            'weekDays' => $this->getWeekDays(),
        ]);
    }

    private function getWeekDays(): array
    {
        $startOfWeek = Carbon::parse($this->currentDate)->startOfWeek();
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'day_name' => $day->translatedFormat('l'),
                'day_number' => $day->day,
                'is_today' => $day->isToday(),
            ];
        }

        return $days;
    }
}
