<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Livewire;

use Livewire\Component;
use Modules\BeautyMasters\Application\Services\AppointmentService;
use Modules\BeautyMasters\Infrastructure\Models\AppointmentModel;
use Modules\BeautyMasters\Domain\Entities\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class MasterCalendar extends Component
{
    public int $masterId;
    public string $currentDate;
    public string $view = 'day';
    public array $appointments = [];
    public array $schedule = [];
    public array $blockedSlots = [];

    protected $listeners = [
        'appointmentCompleted' => 'refreshCalendar',
        'appointmentStarted' => 'refreshCalendar',
        'refreshCalendar' => 'loadData',
    ];

    public function mount(int $masterId): void
    {
        $this->masterId = $masterId;
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->loadAppointments();
        $this->loadSchedule();
        $this->loadBlockedSlots();
    }

    public function loadAppointments(): void
    {
        $dateRange = $this->getDateRange();
        $cacheKey = "beauty:master_calendar:{$this->masterId}:{$this->currentDate}:{$this->view}";

        $this->appointments = Cache::remember($cacheKey, now()->addMinutes(1), function () use ($dateRange) {
            return AppointmentModel::where('master_id', $this->masterId)
                ->whereBetween('start_time', [$dateRange['start'], $dateRange['end']])
                ->with(['client', 'service'])
                ->orderBy('start_time')
                ->get()
                ->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'title' => $appointment->client->full_name,
                    'start' => $appointment->start_time->format('Y-m-d H:i:s'),
                    'end' => $appointment->end_time->format('Y-m-d H:i:s'),
                    'service_name' => $appointment->service->name,
                    'status' => $appointment->status,
                    'color' => $this->getStatusColor($appointment->status),
                    'price' => $appointment->final_price,
                    'notes' => $appointment->notes,
                    'client_phone' => $appointment->client->phone,
                ])
                ->toArray();
        });
    }

    public function loadSchedule(): void
    {
        $dayOfWeek = strtolower(Carbon::parse($this->currentDate)->format('l'));
        
        $schedule = \Modules\BeautyMasters\Infrastructure\Models\MasterScheduleModel::where('master_id', $this->masterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $this->currentDate);
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $this->currentDate);
            })
            ->first();

        $this->schedule = $schedule ? [
            'start' => $schedule->start_time->format('H:i'),
            'end' => $schedule->end_time->format('H:i'),
            'break_start' => $schedule->break_start?->format('H:i'),
            'break_end' => $schedule->break_end?->format('H:i'),
        ] : null;
    }

    public function loadBlockedSlots(): void
    {
        $dateRange = $this->getDateRange();

        $this->blockedSlots = \Modules\BeautyMasters\Infrastructure\Models\BlockedSlotModel::where('master_id', $this->masterId)
            ->whereBetween('start_time', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->map(fn ($slot) => [
                'id' => $slot->id,
                'start' => $slot->start_time->format('Y-m-d H:i:s'),
                'end' => $slot->end_time->format('Y-m-d H:i:s'),
                'reason' => $slot->reason,
                'description' => $slot->description,
            ])
            ->toArray();
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
            default => [
                'start' => $date->startOfDay()->toDateTimeString(),
                'end' => $date->endOfDay()->toDateTimeString(),
            ],
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            AppointmentStatus::PENDING => '#F59E0B',
            AppointmentStatus::CONFIRMED => '#3B82F6',
            AppointmentStatus::IN_PROGRESS => '#F97316',
            AppointmentStatus::COMPLETED => '#10B981',
            AppointmentStatus::CANCELLED => '#EF4444',
            AppointmentStatus::NO_SHOW => '#EF4444',
            AppointmentStatus::PAID => '#10B981',
            default => '#6B7280',
        };
    }

    public function startAppointment(int $appointmentId): void
    {
        // Dispatch event to start appointment
        $this->dispatch('appointmentStarted', $appointmentId);
    }

    public function completeAppointment(int $appointmentId): void
    {
        // Dispatch event to complete appointment
        $this->dispatch('appointmentCompleted', $appointmentId);
    }

    public function previousDay(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subDay()->format('Y-m-d');
        $this->loadData();
    }

    public function nextDay(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addDay()->format('Y-m-d');
        $this->loadData();
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function render()
    {
        return view('beauty-masters::livewire.master-calendar', [
            'currentDateFormatted' => Carbon::parse($this->currentDate)->translatedFormat('d F Y'),
            'isWorkingDay' => !empty($this->schedule),
        ]);
    }
}
