<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Application\Services;

use Modules\BeautyMasters\Domain\Entities\Appointment;
use Modules\BeautyMasters\Domain\Entities\AppointmentStatus;
use Modules\BeautyMasters\Domain\Repositories\AppointmentRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\MasterRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\ServiceRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\MasterScheduleRepositoryInterface;
use Modules\BeautyMasters\Domain\Repositories\BlockedSlotRepositoryInterface;
use Modules\BeautyMasters\Domain\DTOs\CreateAppointmentDTO;
use Modules\BeautyMasters\Domain\DTOs\UpdateAppointmentDTO;
use Modules\BeautyMasters\Domain\ValueObjects\TimeSlot;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Log\LogManager;
use App\Services\Fraud\FraudControlService;
use App\Domain\Audit\Events\AuditEvent;
use App\Traits\WithAnalyticsTracking;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final readonly class AppointmentService
{
    use WithAnalyticsTracking;

    public function __construct(
        private AppointmentRepositoryInterface $appointmentRepository,
        private MasterRepositoryInterface $masterRepository,
        private ServiceRepositoryInterface $serviceRepository,
        private readonly FraudControlService $fraudControl,
        private MasterScheduleRepositoryInterface $scheduleRepository,
        private BlockedSlotRepositoryInterface $blockedSlotRepository,
        private readonly ConnectionInterface $db,
        private readonly Repository $cache,
        private readonly LogManager $log,
    ) {
    }

    public function createAppointment(CreateAppointmentDTO $dto): Appointment
    {// FRAUD CHECK - мандаторно первым действием (Canon 1)
        $fraudResult = $this->fraudControl->checkRequest([
            'operation_type' => 'create_appointment',
            'vertical' => 'beauty_masters',
            'user_id' => $dto->clientId,
            'tenant_id' => $dto->venueId,
            'amount' => $dto->getFinalPrice(),
            'action' => 'create_appointment',
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $correlationId = (string) Str::uuid();
            Event::dispatch(AuditEvent::action(
                action: 'beauty_appointment_blocked_by_fraud',
                subjectType: 'beauty_appointment',
                subjectId: null,
                context: [
                    'fraud_score' => $fraudResult['fraud_score'],
                    'indicators' => $fraudResult['indicators'],
                    'client_id' => $dto->clientId,
                    'venue_id' => $dto->venueId,
                ],
                correlationId: $correlationId
            ));
            throw new \RuntimeException('Appointment creation blocked by fraud detection');
        }

        
        return $this->db->transaction(function () use ($dto) {
            $this->validateAppointmentCreation($dto);

            $appointment = $this->appointmentRepository->save(
                new Appointment(
                    id: 0,
                    venueId: $dto->venueId,
                    masterId: $dto->masterId,
                    clientId: $dto->clientId,
                    serviceId: $dto->serviceId,
                    startTime: $dto->startTime,
                    endTime: $dto->endTime,
                    status: AppointmentStatus::PENDING,
                    price: $dto->price,
                    discountAmount: $dto->discountAmount ?? 0.0,
                    finalPrice: $dto->getFinalPrice(),
                    currency: $dto->currency,
                    paymentStatus: 'unpaid',
                    paymentId: null,
                    notes: $dto->notes,
                    clientNotes: $dto->clientNotes,
                    isOnlineBooking: $dto->isOnlineBooking,
                    bookingSource: $dto->bookingSource,
                    confirmedAt: null,
                    completedAt: null,
                    cancelledAt: null,
                    cancellationReason: null,
                    reminderSent24h: 0,
                    reminderSent2h: 0,
                    metadata: null,
                    createdAt: new \DateTimeImmutable(),
                    updatedAt: null,
                    deletedAt: null,
                )
            );

            $this->invalidateScheduleCache($dto->masterId, $dto->startTime);

            // AUDIT LOG
            $this->logCreated(
                entityType: 'beauty_appointment',
                entityId: $appointment->id,
                context: [
                    'venue_id' => $dto->venueId,
                    'master_id' => $dto->masterId,
                    'service_id' => $dto->serviceId,
                    'start_time' => $dto->startTime->format('Y-m-d H:i:s'),
                    'end_time' => $dto->endTime->format('Y-m-d H:i:s'),
                    'final_price' => $dto->getFinalPrice(),
                    'currency' => $dto->currency,
                ],
                userId: $dto->clientId,
                tenantId: $dto->venueId
            );

            $this->logCreated(
                entityType: 'Appointment',
                entityId: $appointment->id,
                userId: $dto->clientId,
                tenantId: $dto->venueId,
                context: [
                    'master_id' => $dto->masterId,
                    'start_time' => $dto->startTime->format('Y-m-d H:i:s'),
                    'end_time' => $dto->endTime->format('Y-m-d H:i:s'),
                    'final_price' => $dto->getFinalPrice(),
                    'currency' => $dto->currency,
                ]
            );

            // Track behavioral event for analytics
            $this->trackEvent(
                eventType: 'appointment_created',
                vertical: 'beauty_masters',
                targetId: (string) $appointment->id,
                payload: [
                    'master_id' => $dto->masterId,
                    'service_id' => $dto->serviceId,
                    'venue_id' => $dto->venueId,
                    'final_price' => $dto->getFinalPrice(),
                    'currency' => $dto->currency,
                    'is_online_booking' => $dto->isOnlineBooking,
                    'booking_source' => $dto->bookingSource,
                ],
                monetaryValue: $dto->getFinalPrice(),
                userId: $dto->clientId,
            );

            return $appointment;
        });
    }

    public function updateAppointment(UpdateAppointmentDTO $dto): Appointment
    {
        return $this->db->transaction(function () use ($dto) {
            $appointment = $this->appointmentRepository->findById($dto->id);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if ($dto->startTime || $dto->endTime) {
                $timeSlot = new TimeSlot(
                    $dto->startTime ?? $appointment->startTime,
                    $dto->endTime ?? $appointment->endTime
                );

                $overlapping = $this->appointmentRepository->findOverlapping(
                    $dto->masterId ?? $appointment->masterId,
                    $timeSlot->startTime,
                    $timeSlot->endTime,
                    $dto->id
                );

                if (!$overlapping->isEmpty()) {
                    throw new \RuntimeException('Time slot is already booked');
                }
            }

            $updatedAppointment = $this->appointmentRepository->save($appointment);

            if ($dto->masterId || $dto->startTime) {
                $this->invalidateScheduleCache(
                    $dto->masterId ?? $appointment->masterId,
                    $dto->startTime ?? $appointment->startTime
                );
            }

            return $updatedAppointment;
        });
    }

    public function confirmAppointment(int $appointmentId): Appointment
    {
        return $this->db->transaction(function () use ($appointmentId) {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!in_array($appointment->status, [AppointmentStatus::PENDING], true)) {
                throw new \RuntimeException('Appointment cannot be confirmed');
            }

            $this->appointmentRepository->updateStatus($appointmentId, AppointmentStatus::CONFIRMED);

            return $this->appointmentRepository->findById($appointmentId);
        });
    }

    public function startAppointment(int $appointmentId): Appointment
    {
        return $this->db->transaction(function () use ($appointmentId) {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!in_array($appointment->status, [AppointmentStatus::CONFIRMED, AppointmentStatus::PAID], true)) {
                throw new \RuntimeException('Appointment cannot be started');
            }

            $this->appointmentRepository->updateStatus($appointmentId, AppointmentStatus::IN_PROGRESS);

            return $this->appointmentRepository->findById($appointmentId);
        });
    }

    public function completeAppointment(int $appointmentId): Appointment
    {
        return $this->db->transaction(function () use ($appointmentId) {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if ($appointment->status !== AppointmentStatus::IN_PROGRESS) {
                throw new \RuntimeException('Appointment is not in progress');
            }

            $this->appointmentRepository->updateStatus($appointmentId, AppointmentStatus::COMPLETED);

            return $this->appointmentRepository->findById($appointmentId);
        });
    }

    public function cancelAppointment(int $appointmentId, string $reason): Appointment
    {
        return $this->db->transaction(function () use ($appointmentId, $reason) {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if (!$appointment->canBeCancelled()) {
                throw new \RuntimeException('Appointment cannot be cancelled');
            }

            $this->appointmentRepository->updateStatus($appointmentId, AppointmentStatus::CANCELLED);

            $this->invalidateScheduleCache($appointment->masterId, $appointment->startTime);

            return $this->appointmentRepository->findById($appointmentId);
        });
    }

    public function markAsNoShow(int $appointmentId): Appointment
    {
        return $this->db->transaction(function () use ($appointmentId) {
            $appointment = $this->appointmentRepository->findById($appointmentId);
            if (!$appointment) {
                throw new \InvalidArgumentException('Appointment not found');
            }

            if ($appointment->isPast()) {
                $this->appointmentRepository->updateStatus($appointmentId, AppointmentStatus::NO_SHOW);
            } else {
                throw new \RuntimeException('Cannot mark future appointment as no-show');
            }

            return $this->appointmentRepository->findById($appointmentId);
        });
    }

    public function getAvailableSlots(
        int $masterId,
        int $serviceId,
        \DateTimeImmutable $date
    ): array {
        $cacheKey = "beauty:available_slots:{$masterId}:{$serviceId}:{$date->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, now()->addMinutes(5), function () use ($masterId, $serviceId, $date) {
            $master = $this->masterRepository->findById($masterId);
            if (!$master) {
                throw new \InvalidArgumentException('Master not found');
            }

            $service = $this->serviceRepository->findById($serviceId);
            if (!$service) {
                throw new \InvalidArgumentException('Service not found');
            }

            $dayOfWeek = strtolower($date->format('l'));
            $schedule = $this->scheduleRepository->findByMasterIdAndDay($masterId, $dayOfWeek);

            if (!$schedule || !$schedule['is_working_day']) {
                return [];
            }

            $workingStart = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $date->format('Y-m-d') . ' ' . $schedule['start_time']->format('H:i:s')
            );
            $workingEnd = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $date->format('Y-m-d') . ' ' . $schedule['end_time']->format('H:i:s')
            );

            $blockedSlots = $this->blockedSlotRepository->findByMasterId(
                $masterId,
                $workingStart,
                $workingEnd
            );

            $existingAppointments = $this->appointmentRepository->findByMasterId(
                $masterId,
                $workingStart,
                $workingEnd
            );

            $slots = [];
            $currentTime = $workingStart;
            $serviceDuration = $service->durationMinutes + $service->bufferMinutes;

            while ($currentTime->modify("+{$serviceDuration} minutes") <= $workingEnd) {
                $slotEnd = $currentTime->modify("+{$serviceDuration} minutes");

                $timeSlot = new TimeSlot($currentTime, $slotEnd);

                $isBlocked = $blockedSlots->contains(function ($blocked) use ($timeSlot) {
                    return $timeSlot->overlaps(new TimeSlot(
                        new \DateTimeImmutable($blocked['start_time']),
                        new \DateTimeImmutable($blocked['end_time'])
                    ));
                });

                $isBooked = $existingAppointments->contains(function ($appointment) use ($timeSlot) {
                    return $timeSlot->overlaps(new TimeSlot(
                        $appointment->startTime,
                        $appointment->endTime
                    ));
                });

                if (!$isBlocked && !$isBooked && $currentTime > new \DateTimeImmutable()) {
                    $slots[] = [
                        'start_time' => $currentTime->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                    ];
                }

                $currentTime = $slotEnd;
            }

            return $slots;
        });
    }

    private function validateAppointmentCreation(CreateAppointmentDTO $dto): void
    {
        $master = $this->masterRepository->findById($dto->masterId);
        if (!$master || !$master->isActive) {
            throw new \InvalidArgumentException('Master not found or inactive');
        }

        $service = $this->serviceRepository->findById($dto->serviceId);
        if (!$service || !$service->isActive) {
            throw new \InvalidArgumentException('Service not found or inactive');
        }

        $dayOfWeek = strtolower($dto->startTime->format('l'));
        $schedule = $this->scheduleRepository->findByMasterIdAndDay($dto->masterId, $dayOfWeek);

        if (!$schedule || !$schedule['is_working_day']) {
            throw new \RuntimeException('Master is not working on this day');
        }

        $timeSlot = new TimeSlot($dto->startTime, $dto->endTime);

        $overlapping = $this->appointmentRepository->findOverlapping(
            $dto->masterId,
            $dto->startTime,
            $dto->endTime
        );

        if (!$overlapping->isEmpty()) {
            throw new \RuntimeException('Time slot is already booked');
        }

        $blockedSlots = $this->blockedSlotRepository->findOverlapping(
            $dto->masterId,
            $dto->startTime,
            $dto->endTime
        );

        if (!$blockedSlots->isEmpty()) {
            throw new \RuntimeException('Time slot is blocked');
        }
    }

    private function invalidateScheduleCache(int $masterId, \DateTimeImmutable $date): void
    {
        $cacheKey = "beauty:available_slots:{$masterId}:*:{$date->format('Y-m-d')}";
        $this->cache->forget($cacheKey);
    }
}
