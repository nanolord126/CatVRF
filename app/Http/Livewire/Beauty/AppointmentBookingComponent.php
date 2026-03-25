<?php declare(strict_types=1);

namespace App\Http\Livewire\Beauty;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Services\AppointmentService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;

final class AppointmentBookingComponent extends Component
{
    public int $masterId = 0;
    public int $serviceId = 0;
    public ?string $appointmentDate = null;
    public ?string $appointmentTime = null;
    public array $availableSlots = [];
    public bool $isSubmitting = false;
    public ?string $message = null;

    protected AppointmentService $appointmentService;

    public function mount(AppointmentService $appointmentService): void
    {
        $this->appointmentService = $appointmentService;
    }

    public function updatedAppointmentDate(): void
    {
        if ($this->appointmentDate && $this->masterId) {
            $this->loadAvailableSlots();
        }
    }

    public function loadAvailableSlots(): void
    {
        try {
            $date = Carbon::parse($this->appointmentDate);
            $this->availableSlots = $this->appointmentService->getAvailableSlots(
                $this->masterId,
                $date
            );
        } catch (\Exception $e) {
            \$this->log->channel('error')->error('Failed to load available slots', [
                'master_id' => $this->masterId,
                'date' => $this->appointmentDate,
                'exception' => $e->getMessage(),
                'correlation_id' => (string) Str::uuid(),
            ]);

            $this->message = 'Ошибка при загрузке доступных слотов';
        }
    }

    public function bookAppointment(): void
    {
        $this->validate([
            'masterId' => 'required|integer|exists:masters,id',
            'serviceId' => 'required|integer|exists:services,id',
            'appointmentDate' => 'required|date',
            'appointmentTime' => 'required|date_format:H:i',
        ]);

        $this->isSubmitting = true;

        try {
            $dateTime = Carbon::parse("{$this->appointmentDate} {$this->appointmentTime}");

            $appointment = $this->appointmentService->createAppointment([
                'master_id' => $this->masterId,
                'service_id' => $this->serviceId,
                'client_id' => auth()->user()->id,
                'datetime' => $dateTime,
                'correlation_id' => (string) Str::uuid(),
            ]);

            $this->emit('appointmentBooked', $appointment->id);
            $this->message = 'Запись успешно создана!';

            \$this->log->channel('audit')->info('Appointment booked', [
                'appointment_id' => $appointment->id,
                'master_id' => $this->masterId,
                'user_id' => auth()->user()->id,
                'correlation_id' => $appointment->correlation_id,
            ]);

            // Reset form
            $this->reset(['masterId', 'serviceId', 'appointmentDate', 'appointmentTime']);

        } catch (\Exception $e) {
            \$this->log->channel('error')->error('Failed to book appointment', [
                'exception' => $e->getMessage(),
                'correlation_id' => (string) Str::uuid(),
            ]);

            $this->message = 'Ошибка при бронировании. Пожалуйста, попробуйте снова.';

        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        return view('livewire.beauty.appointment-booking', [
            'availableSlots' => $this->availableSlots,
        ]);
    }
}
