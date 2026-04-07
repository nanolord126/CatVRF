<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\CreateAppointmentDto;
use App\Domains\Beauty\Events\AppointmentCancelled;
use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Events\AppointmentCreated;
use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\MasterSchedule;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * AppointmentService — управление записями к мастерам в салонах красоты.
 *
 * CANON 2026: FraudControlService::check() + DB::transaction() + correlation_id + AuditService.
 * Никаких фасадов, только constructor injection. DTO на входе.
 */
final readonly class AppointmentService
{
    public function __construct(
        private FraudControlService $fraud,
        private ConsumableDeductionService $consumableService,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Создать запись (бронирование) к мастеру.
     *
     * 1. Fraud-check
     * 2. Проверка доступности слота
     * 3. Создание Appointment
     * 4. Блокировка слота в расписании
     * 5. Резерв расходников
     * 6. Событие AppointmentCreated
     * 7. Audit log
     */
    public function createAppointment(CreateAppointmentDto $dto): Appointment
    {
        $correlationId = $dto->getCorrelationId();
        $data = $dto->toArray();

        $this->fraud->check(
            userId: $dto->getUserId(),
            operationType: 'beauty_create_appointment',
            amount: (int) ($data['price'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): Appointment {
            $masterId = (int) $data['master_id'];
            $start = Carbon::parse($data['datetime_start']);
            $end = Carbon::parse($data['datetime_end']);

            $isAvailable = $this->checkSlotAvailability($masterId, $start, $end);

            if (! $isAvailable) {
                throw new \DomainException(
                    "Мастер (id={$masterId}) недоступен в выбранное время: {$start->toDateTimeString()} — {$end->toDateTimeString()}"
                );
            }

            $appointment = Appointment::create(array_merge($data, [
                'uuid' => Str::uuid()->toString(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'correlation_id' => $correlationId,
            ]));

            $this->blockMasterSlot($masterId, $start);

            $this->consumableService->reserveForAppointment($appointment);

            event(new AppointmentCreated(
                appointmentId: $appointment->id,
                clientId: $appointment->user_id,
                masterId: $appointment->master_id,
                tenantId: $appointment->tenant_id,
                scheduledAt: $start->toDateTimeString(),
                priceKopecks: (int) $appointment->price,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'beauty_appointment_created',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: [],
                newValues: $appointment->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty appointment created', [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'master_id' => $masterId,
                'salon_id' => $appointment->salon_id,
                'start' => $start->toDateTimeString(),
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Подтвердить запись (мастер/салон подтверждает).
     */
    public function confirmAppointment(Appointment $appointment, string $correlationId = ''): Appointment
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_confirm_appointment',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointment, $correlationId): Appointment {
            if ($appointment->status !== 'pending') {
                throw new \DomainException(
                    "Подтвердить можно только 'pending'. Текущий: {$appointment->status}"
                );
            }

            $oldStatus = $appointment->status;
            $appointment->update([
                'status' => 'confirmed',
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'beauty_appointment_confirmed',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'confirmed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty appointment confirmed', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Завершить запись (услуга оказана).
     *
     * Финансовая обработка идёт через ProcessAppointmentPaymentJob (async).
     */
    public function completeAppointment(Appointment $appointment, string $correlationId = ''): Appointment
    {
        $correlationId = $correlationId !== ''
            ? $correlationId
            : ($appointment->correlation_id ?? Str::uuid()->toString());

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_complete_appointment',
            amount: (int) $appointment->price,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointment, $correlationId): Appointment {
            if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
                throw new \DomainException(
                    "Завершить можно только 'pending'/'confirmed'. Текущий: {$appointment->status}"
                );
            }

            $oldStatus = $appointment->status;
            $appointment->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->consumableService->deductForAppointment($appointment);

            event(new AppointmentCompleted(
                appointmentId: $appointment->id,
                masterId: $appointment->master_id,
                payoutKopecks: (int) $appointment->price,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'beauty_appointment_completed',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty appointment completed', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Отменить запись.
     *
     * Освобождает слот мастера и зарезервированные расходники.
     */
    public function cancelAppointment(Appointment $appointment, string $correlationId = ''): Appointment
    {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_cancel_appointment',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointment, $correlationId): Appointment {
            if ($appointment->status === 'completed') {
                throw new \DomainException('Завершённую запись отменить нельзя.');
            }

            if ($appointment->status === 'cancelled') {
                throw new \DomainException('Запись уже отменена.');
            }

            $oldStatus = $appointment->status;
            $appointment->update([
                'status' => 'cancelled',
                'correlation_id' => $correlationId,
            ]);

            $this->releaseMasterSlot(
                $appointment->master_id,
                Carbon::parse($appointment->datetime_start),
            );

            $this->consumableService->releaseForAppointment($appointment);

            event(new AppointmentCancelled(
                appointmentId: $appointment->id,
                masterId: $appointment->master_id,
                correlationId: $correlationId,
            ));

            $this->audit->record(
                action: 'beauty_appointment_cancelled',
                subjectType: Appointment::class,
                subjectId: $appointment->id,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty appointment cancelled', [
                'appointment_id' => $appointment->id,
                'old_status' => $oldStatus,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    /**
     * Проверка доступности слота: нет пересечений с другими активными записями.
     */
    private function checkSlotAvailability(int $masterId, Carbon $start, Carbon $end): bool
    {
        return ! Appointment::where('master_id', $masterId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($start, $end): void {
                $query->where(function ($q) use ($start, $end): void {
                    $q->where('datetime_start', '<', $end)
                      ->where('datetime_end', '>', $start);
                });
            })
            ->exists();
    }

    /**
     * Заблокировать слот в расписании мастера.
     */
    private function blockMasterSlot(int $masterId, Carbon $start): void
    {
        $schedule = MasterSchedule::where('master_id', $masterId)
            ->where('date', $start->toDateString())
            ->first();

        if ($schedule !== null) {
            $schedule->blockSlot($start->format('H:i'));
        }
    }

    /**
     * Освободить слот в расписании мастера при отмене.
     */
    private function releaseMasterSlot(int $masterId, Carbon $start): void
    {
        $schedule = MasterSchedule::where('master_id', $masterId)
            ->where('date', $start->toDateString())
            ->first();

        if ($schedule !== null) {
            $schedule->releaseSlot($start->format('H:i'));
        }
    }
}
