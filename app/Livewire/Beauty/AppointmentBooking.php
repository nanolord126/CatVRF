<?php declare(strict_types=1);

namespace App\Livewire\Beauty;

use App\Domains\Beauty\Events\AppointmentScheduled;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Services\AppointmentService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Rule;
use Livewire\Component;

/**
 * Livewire-компонент бронирования услуги (КАНОН 2026).
 * Полная интеграция с AppointmentService, FraudControlService, RateLimiter.
 */
final class AppointmentBooking extends Component
{
    // -------------------------------------------------------------------------
    // Props
    // -------------------------------------------------------------------------

    public int $salonId;

    // -------------------------------------------------------------------------
    // Form fields
    // -------------------------------------------------------------------------

    #[Rule('required|integer|min:1', message: 'Выберите мастера')]
    public ?int $masterId = null;

    #[Rule('required|integer|min:1', message: 'Выберите услугу')]
    public ?int $serviceId = null;

    #[Rule('required|date|after_or_equal:today', message: 'Выберите дату')]
    public string $selectedDate = '';

    #[Rule('required|date_format:H:i', message: 'Выберите время')]
    public string $selectedTime = '';

    #[Rule('nullable|string|max:500')]
    public string $comment = '';

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    public array $availableSlots = [];
    public array $masters = [];
    public array $services = [];
    public ?string $errorMessage = null;
    public bool $booked = false;
    public ?int $createdAppointmentId = null;

    // -------------------------------------------------------------------------
    // Mount
    // -------------------------------------------------------------------------

    public function mount(int $salonId): void
    {
        $this->salonId = $salonId;
        $this->loadMasters();
    }

    // -------------------------------------------------------------------------
    // Loaders
    // -------------------------------------------------------------------------

    public function loadMasters(): void
    {
        $this->masters = Master::where('salon_id', $this->salonId)
            ->where('tenant_id', tenant('id'))
            ->select('id', 'full_name', 'specialization')
            ->get()
            ->toArray();
    }

    public function updatedMasterId(?int $value): void
    {
        $this->services = [];
        $this->availableSlots = [];
        $this->selectedDate = '';
        $this->selectedTime = '';

        if ($value) {
            $this->loadServices($value);
        }
    }

    private function loadServices(int $masterId): void
    {
        $this->services = BeautyService::where('master_id', $masterId)
            ->where('tenant_id', tenant('id'))
            ->select('id', 'name', 'duration_minutes', 'price')
            ->get()
            ->toArray();
    }

    public function loadAvailableSlots(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedTime = '';
        $this->availableSlots = [];

        if (!$this->masterId || !$this->serviceId) {
            return;
        }

        $service = collect($this->services)->firstWhere('id', $this->serviceId);
        $duration = $service['duration_minutes'] ?? 60;

        // Получить занятые слоты из БД
        $busySlots = \App\Domains\Beauty\Models\Appointment::where('master_id', $this->masterId)
            ->whereDate('datetime_start', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('datetime_start')
            ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();

        // Сгенерировать свободные слоты (9:00–20:00 с шагом duration)
        $slots = [];
        $start = Carbon::parse($date . ' 09:00');
        $end = Carbon::parse($date . ' 20:00');

        while ($start->copy()->addMinutes($duration)->lte($end)) {
            $timeStr = $start->format('H:i');
            if (!in_array($timeStr, $busySlots, true)) {
                $slots[] = $timeStr;
            }
            $start->addMinutes($duration);
        }

        $this->availableSlots = $slots;
    }

    // -------------------------------------------------------------------------
    // Book
    // -------------------------------------------------------------------------

    public function bookAppointment(
        AppointmentService $appointmentService,
        FraudControlService $fraudControlService,
    ): void {
        $this->errorMessage = null;
        $correlationId = (string) Str::uuid()->toString();

        // Rate limiting — не более 5 бронирований в час с одного пользователя
        $rateLimitKey = 'beauty:booking:' . $this->auth->id() . ':' . tenant('id');
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->errorMessage = 'Слишком много попыток. Попробуйте позже.';
            $this->log->channel('audit')->warning('Beauty: Rate limit exceeded on booking', [
                'user_id' => $this->auth->id(),
                'correlation_id' => $correlationId,
            ]);
            return;
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $this->validate();

        // Fraud check (instance-метод, DI через параметр action)
        $fraudResult = $fraudControlService->check(
            userId: $this->auth->id(),
            operationType: 'appointment_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->errorMessage = 'Бронирование временно недоступно. Обратитесь в поддержку.';
            $this->log->channel('audit')->warning('Beauty: Fraud block on booking', [
                'user_id' => $this->auth->id(),
                'score' => $fraudResult['score'],
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $service = collect($this->services)->firstWhere('id', $this->serviceId);
        $consumables = collect($service['consumables_json'] ?? [])->all();

        try {
            $dateTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);

            $appointment = $appointmentService->bookAppointment(
                masterId: $this->masterId,
                serviceId: $this->serviceId,
                dateTime: $dateTime,
                consumables: $consumables,
                correlationId: $correlationId,
            );

            event(new AppointmentScheduled($appointment, $correlationId));

            $this->booked = true;
            $this->createdAppointmentId = $appointment->id;

            $this->log->channel('audit')->info('Beauty: Appointment booked via Livewire', [
                'appointment_id' => $appointment->id,
                'user_id' => $this->auth->id(),
                'salon_id' => $this->salonId,
                'master_id' => $this->masterId,
                'service_id' => $this->serviceId,
                'datetime' => $dateTime->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            $this->dispatch('appointment-booked', appointmentId: $appointment->id);

        } catch (\Exception $e) {
            $this->errorMessage = 'Не удалось создать запись: ' . $e->getMessage();
            $this->log->channel('audit')->error('Beauty: Booking failed in Livewire', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->auth->id(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('livewire.beauty.appointment-booking');
    }
}
