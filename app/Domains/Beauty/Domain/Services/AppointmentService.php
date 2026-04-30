<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\DTOs\BookAppointmentDto;
use App\Domains\Beauty\Events\AppointmentBooked;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Carbon\Carbon;
use RuntimeException;
use App\DTOs\OperationDto;

final readonly class AppointmentService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly Dispatcher $events
    ) {}

    public function book(BookAppointmentDto $dto): Appointment
    {
        // No Facades. Injection only.
        $this->fraud->check(new OperationDto(
            userId: $dto->userId,
            operationType: 'book_beauty_appointment',
            amount: 0.0,
            correlationId: $dto->correlationId,
            isB2B: $dto->isB2b
        ));

        return $this->db->transaction(function () use ($dto) {
            $service = BeautyService::findOrFail($dto->serviceId);
            $start = Carbon::parse($dto->startsAt);
            $end = $start->copy()->addMinutes($service->duration_minutes);

            $exists = Appointment::where('master_id', $dto->masterId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('starts_at', [$start, $end])
                        ->orWhereBetween('ends_at', [$start, $end]);
                })->exists();

            if ($exists) {
                throw new RuntimeException('Slot is no longer available');
            }

            $price = $dto->isB2b ? $service->price_b2b : $service->price_b2c;

            $appointment = Appointment::create([
                'tenant_id' => $dto->tenantId,
                'salon_id' => $dto->salonId,
                'master_id' => $dto->masterId,
                'service_id' => $dto->serviceId,
                'user_id' => $dto->userId,
                'status' => 'pending',
                'starts_at' => $start,
                'ends_at' => $end,
                'total_price' => $price,
                'is_b2b' => $dto->isB2b,
                'correlation_id' => $dto->correlationId,
                'uuid' => Str::uuid()->toString(),
            ]);

            $this->audit->log(
                'appointment_booked',
                Appointment::class,
                $appointment->id,
                [],
                $appointment->toArray(),
                $dto->correlationId
            );

            $this->events->dispatch(new AppointmentBooked($appointment, $dto->correlationId));

            return $appointment;
        });
    }

    public function moveAppointment(int $appointmentId, int $newMasterId, string $newDate, string $newTime, string $correlationId): Appointment
    {
        $this->fraud->check(new OperationDto(
            userId: auth()->id(),
            operationType: 'move_beauty_appointment',
            amount: 0.0,
            correlationId: $correlationId,
            isB2B: false
        ));

        return $this->db->transaction(function () use ($appointmentId, $newMasterId, $newDate, $newTime, $correlationId) {
            $appointment = Appointment::findOrFail($appointmentId);
            $service = BeautyService::findOrFail($appointment->service_id);
            
            $start = Carbon::parse("{$newDate} {$newTime}");
            $end = $start->copy()->addMinutes($service->duration_minutes);

            // Check for conflicts at new time slot
            $exists = Appointment::where('master_id', $newMasterId)
                ->where('id', '!=', $appointmentId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('starts_at', [$start, $end])
                        ->orWhereBetween('ends_at', [$start, $end]);
                })->exists();

            if ($exists) {
                throw new RuntimeException('Target slot is not available');
            }

            $oldData = $appointment->toArray();

            $appointment->update([
                'master_id' => $newMasterId,
                'starts_at' => $start,
                'ends_at' => $end,
            ]);

            $this->audit->log(
                'appointment_moved',
                Appointment::class,
                $appointment->id,
                $oldData,
                $appointment->toArray(),
                $correlationId
            );

            return $appointment->fresh();
        });
    }

    public function cancelAppointment(int $appointmentId, string $reason, string $correlationId): Appointment
    {
        $this->fraud->check(new OperationDto(
            userId: auth()->id(),
            operationType: 'cancel_beauty_appointment',
            amount: 0.0,
            correlationId: $correlationId,
            isB2B: false
        ));

        return $this->db->transaction(function () use ($appointmentId, $reason, $correlationId) {
            $appointment = Appointment::findOrFail($appointmentId);

            if ($appointment->status === 'cancelled') {
                throw new RuntimeException('Appointment is already cancelled');
            }

            $oldData = $appointment->toArray();

            $appointment->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            $this->audit->log(
                'appointment_cancelled',
                Appointment::class,
                $appointment->id,
                $oldData,
                $appointment->toArray(),
                $correlationId
            );

            return $appointment->fresh();
        });
    }

    public function updateStatus(int $appointmentId, string $status, string $correlationId): Appointment
    {
        $this->fraud->check(new OperationDto(
            userId: auth()->id(),
            operationType: 'update_appointment_status',
            amount: 0.0,
            correlationId: $correlationId,
            isB2B: false
        ));

        return $this->db->transaction(function () use ($appointmentId, $status, $correlationId) {
            $appointment = Appointment::findOrFail($appointmentId);

            $oldData = $appointment->toArray();

            $appointment->update(['status' => $status]);

            $this->audit->log(
                'appointment_status_updated',
                Appointment::class,
                $appointment->id,
                $oldData,
                $appointment->toArray(),
                $correlationId
            );

            return $appointment->fresh();
        });
    }
}
