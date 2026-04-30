<?php

declare(strict_types=1);

namespace App\Http\Livewire\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Master;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * MasterCalendar — персональный календарь мастера.
 * 
 * Мобильный/планшетный вид с крупным шрифтом.
 * Быстрые действия: «Клиент пришёл», «Услуга начата», «Завершена».
 * Отображение времени до окончания текущей услуги.
 */
final class MasterCalendar extends Component
{
    public string $currentDate = '';

    public string $viewMode = 'day'; // day, week

    public array $appointments = [];

    public ?int $currentAppointmentId = null;

    public array $clientNotes = [];

    public function __construct(private readonly Factory $viewFactory) {}

    public function mount(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData(): void
    {
        $master = $this->getMaster();
        
        if (!$master) {
            return;
        }

        $date = Carbon::parse($this->currentDate);
        
        $query = Appointment::where('master_id', $master->id)
            ->where('tenant_id', tenant()->id ?? 1)
            ->with(['service', 'client']);

        if ($this->viewMode === 'day') {
            $query->whereDate('starts_at', $date);
        } else {
            $query->whereBetween('starts_at', [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ]);
        }

        $appointments = $query->orderBy('starts_at')->get();

        $this->appointments = $appointments->map(fn ($appointment) => [
            'id' => $appointment->id,
            'service_name' => $appointment->service->name ?? 'Услуга',
            'client_name' => $appointment->client?->name ?? 'Клиент',
            'client_phone' => $appointment->client?->phone ?? '',
            'start' => $appointment->starts_at->toIso8601String(),
            'end' => $appointment->ends_at->toIso8601String(),
            'status' => $appointment->status,
            'duration_minutes' => $appointment->starts_at->diffInMinutes($appointment->ends_at),
            'is_current' => $appointment->status === 'in_progress',
            'time_remaining' => $appointment->status === 'in_progress' 
                ? max(0, $appointment->ends_at->diffInMinutes(now())) 
                : null,
        ])->toArray();

        // Find current appointment
        $currentAppointment = $appointments->firstWhere('status', 'in_progress');
        $this->currentAppointmentId = $currentAppointment?->id;
    }

    private function getMaster(): ?Master
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return Master::where('user_id', $user->id)
            ->where('tenant_id', tenant()->id ?? 1)
            ->first();
    }

    public function previous(): void
    {
        if ($this->viewMode === 'day') {
            $this->currentDate = Carbon::parse($this->currentDate)
                ->subDay()
                ->format('Y-m-d');
        } else {
            $this->currentDate = Carbon::parse($this->currentDate)
                ->subWeek()
                ->format('Y-m-d');
        }
        $this->loadData();
    }

    public function next(): void
    {
        if ($this->viewMode === 'day') {
            $this->currentDate = Carbon::parse($this->currentDate)
                ->addDay()
                ->format('Y-m-d');
        } else {
            $this->currentDate = Carbon::parse($this->currentDate)
                ->addWeek()
                ->format('Y-m-d');
        }
        $this->loadData();
    }

    public function goToToday(): void
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'day' ? 'week' : 'day';
        $this->loadData();
    }

    public function startAppointment(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        if ($appointment->master_id !== $this->getMaster()?->id) {
            return;
        }

        $appointment->update(['status' => 'in_progress']);
        $this->loadData();
    }

    public function completeAppointment(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        if ($appointment->master_id !== $this->getMaster()?->id) {
            return;
        }

        $appointment->update(['status' => 'completed']);
        $this->loadData();
    }

    public function markNoShow(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        
        if ($appointment->master_id !== $this->getMaster()?->id) {
            return;
        }

        $appointment->update([
            'status' => 'no_show',
            'cancellation_reason' => 'Клиент не пришёл',
        ]);
        $this->loadData();
    }

    public function render()
    {
        $master = $this->getMaster();
        
        return $this->viewFactory->make('livewire.beauty.master-calendar', [
            'master' => $master,
            'currentDate' => Carbon::parse($this->currentDate),
        ]);
    }
}
