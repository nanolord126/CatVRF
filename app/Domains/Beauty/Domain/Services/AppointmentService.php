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
use Throwable;
use RuntimeException;

final readonly class AppointmentService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private Dispatcher $events
    ) {}

    public function book(BookAppointmentDto $dto): Appointment
    {
        // No Facades. Injection only.
        $this->fraud->check(new \App\DTOs\OperationDto(
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
}
