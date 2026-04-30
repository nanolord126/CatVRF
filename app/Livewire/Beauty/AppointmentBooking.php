<?php

declare(strict_types=1);

namespace App\Livewire\Beauty;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Illuminate\Support\Collection;

use Psr\Log\LoggerInterface;

use Illuminate\Auth\AuthManager;
use Livewire\Component;
use Illuminate\Log\LogManager;
use App\Domains\Beauty\Models\Appointment;

final class AppointmentBooking extends Component
{
    // -------------------------------------------------------------------------
    // Props
    // -------------------------------------------------------------------------

    private readonly int $salonId;

    // -------------------------------------------------------------------------
    // Form fields
    // -------------------------------------------------------------------------

    #[Rule('required|integer|min:1', message: 'Выберите мастера')]
    private readonly ?int $masterId = null;

    #[Rule('required|integer|min:1', message: 'Выберите услугу')]
    private readonly ?int $serviceId = null;

    #[Rule('required|date|after_or_equal:today', message: 'Выберите дату')]
    private readonly string $selectedDate = '';

    #[Rule('required|date_format:H:i', message: 'Выберите время')]
    private readonly string $selectedTime = '';

    #[Rule('nullable|string|max:500')]
    private readonly string $comment = '';

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    private readonly array $availableSlots = [];

    private readonly array $masters = [];

    private readonly array $services = [];

    private readonly ?string $errorMessage = null;

    private readonly bool $booked = false;

    private readonly ?int $createdAppointmentId = null;

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly AuthManager $authManager,
        private readonly LogManager $logger,) {}

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

    public function loadAvailableSlots(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedTime = '';
        $this->availableSlots = [];

        if (! $this->masterId || ! $this->serviceId) {
            return;
        }

        $service = new Collection($this->services)->firstWhere('id', $this->serviceId);
        $duration = $service['duration_minutes'] ?? 60;

        // Получить занятые слоты из БД
        $busySlots = Appointment::where('master_id', $this->masterId)
            ->whereDate('datetime_start', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('datetime_start')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'))
            ->toArray();

        // Сгенерировать свободные слоты (9:00–20:00 с шагом duration)
        $slots = [];
        $start = Carbon::parse($date.' 09:00');
        $end = Carbon::parse($date.' 20:00');

        while ($start->copy()->addMinutes($duration)->lte($end)) {
            $timeStr = $start->format('H:i');
            if (! in_array($timeStr, $busySlots, true)) {
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
        FraudControlService $fraud,
    ): void {
        $this->errorMessage = null;
        $correlationId = (string) Str::uuid()->toString();

        // Rate limiting — не более 5 бронирований в час с одного пользователя
        $rateLimitKey = 'beauty:booking:'.$this->authManager->id().':'.tenant('id');
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->errorMessage = 'Слишком много попыток. Попробуйте позже.';
            $this->logger->channel('audit')->warning('Beauty: Rate limit exceeded on booking', [
                'user_id' => $this->authManager->id(),
                'correlation_id' => $correlationId,
            ]);

            return;
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $this->validate();

        // Fraud check (instance-метод, DI через параметр action)
        $fraudResult = $fraudControlService->check(
            userId: $this->authManager->id(),
            operationType: 'appointment_booking',
            amount: 0,
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->errorMessage = 'Бронирование временно недоступно. Обратитесь в поддержку.';
            $this->logger->channel('audit')->warning('Beauty: Fraud block on booking', [
                'user_id' => $this->authManager->id(),
                'score' => $fraudResult['score'],
                'correlation_id' => $correlationId,
            ]);

            return;
        }

        $service = new Collection($this->services)->firstWhere('id', $this->serviceId);
        $consumables = new Collection($service['consumables_json'] ?? [])->all();

        try {
            $dateTime = Carbon::parse($this->selectedDate.' '.$this->selectedTime);

            $appointment = $appointmentService->bookAppointment(
                masterId: $this->masterId,
                serviceId: $this->serviceId,
                dateTime: $dateTime,
                consumables: $consumables,
                correlationId: $correlationId,
            );

            $this->eventDispatcher->dispatch(new AppointmentScheduled($appointment, $correlationId));

            $this->booked = true;
            $this->createdAppointmentId = $appointment->id;

            $this->logger->channel('audit')->$this->logger->info('Beauty: Appointment booked via Livewire', [
                'appointment_id' => $appointment->id,
                'user_id' => $this->authManager->id(),
                'salon_id' => $this->salonId,
                'master_id' => $this->masterId,
                'service_id' => $this->serviceId,
                'datetime' => $dateTime->toIso8601String(),
                'correlation_id' => $correlationId,
            ]);

            $this->dispatch('appointment-booked', appointmentId: $appointment->id);

        } catch (\Exception $e) {
            $this->errorMessage = 'Не удалось создать запись: '.$e->getMessage();
            $this->logger->channel('audit')->error('Beauty: Booking failed in Livewire', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->authManager->id(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return $this->viewFactory->make('livewire.beauty.appointment-booking');
    }

    private function loadServices(int $masterId): void
    {
        $this->services = BeautyService::where('master_id', $masterId)
            ->where('tenant_id', tenant('id'))
            ->select('id', 'name', 'duration_minutes', 'price')
            ->get()
            ->toArray();
    }
}
