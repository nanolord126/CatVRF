<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Booking;
use Modules\Fitness\Domain\Entities\Client;
use Modules\Fitness\Domain\Entities\Membership;
use Modules\Fitness\Domain\Entities\ScheduleSlot;
use Modules\Fitness\Domain\Entities\WorkoutSession;
use Modules\Fitness\Domain\Enums\BookingStatus;
use Modules\Fitness\Domain\Repositories\BookingRepositoryInterface;
use Modules\Fitness\Domain\Repositories\ClientRepositoryInterface;
use Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface;
use Modules\Fitness\Domain\Repositories\ScheduleSlotRepositoryInterface;
use Modules\Fitness\Domain\Repositories\WorkoutSessionRepositoryInterface;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

final readonly class ScheduleService
{
    use WithAuditLogging;

    public function __construct(
        private ScheduleSlotRepositoryInterface $slotRepository,
        private BookingRepositoryInterface $bookingRepository,
        private MembershipRepositoryInterface $membershipRepository,
        private ClientRepositoryInterface $clientRepository,
        private WorkoutSessionRepositoryInterface $sessionRepository,
        private readonly AuditService $audit,
    ) {}

    public function createSlot(
        int $tenantId,
        int $venueId,
        int $trainerId,
        int $workoutTypeId,
        CarbonImmutable $startTime,
        CarbonImmutable $endTime,
        int $capacity = 20,
        bool $isRecurring = false,
        ?string $recurrencePattern = null,
        ?CarbonImmutable $recurrenceEnd = null,
    ): ScheduleSlot {
        $slot = ScheduleSlot::create(
            tenantId: $tenantId,
            venueId: $venueId,
            trainerId: $trainerId,
            workoutTypeId: $workoutTypeId,
            startTime: $startTime,
            endTime: $endTime,
            capacity: $capacity,
            isRecurring: $isRecurring,
            recurrencePattern: $recurrencePattern,
            recurrenceEnd: $recurrenceEnd,
        );

        $saved = $this->slotRepository->save($slot);

        $this->logCreated('ScheduleSlot', $saved->id, [
            'tenant_id' => $tenantId,
            'venue_id' => $venueId,
            'trainer_id' => $trainerId,
            'workout_type_id' => $workoutTypeId,
            'capacity' => $capacity,
        ], null, $tenantId);

        return $saved;
    }

    public function bookSlot(
        int $clientId,
        int $scheduleSlotId,
        ?int $membershipId = null,
        bool $isPaid = false,
        float $price = 0.0,
    ): Booking {
        $slot = $this->slotRepository->findById($scheduleSlotId);
        if (!$slot) {
            throw new \InvalidArgumentException('Schedule slot not found');
        }

        if (!$slot->hasAvailableSlots()) {
            throw new \RuntimeException('No available slots for this session');
        }

        $client = $this->clientRepository->findById($clientId);
        if (!$client) {
            throw new \InvalidArgumentException('Client not found');
        }

        $booking = Booking::create(
            tenantId: $slot->tenantId,
            clientId: $clientId,
            scheduleSlotId: $scheduleSlotId,
            membershipId: $membershipId,
            isPaid: $isPaid,
            price: $price,
        );

        $saved = $this->bookingRepository->save($booking);

        $updatedSlot = $slot->incrementBookedCount();
        $this->slotRepository->save($updatedSlot);

        $this->logCreated('Booking', $saved->id, [
            'client_id' => $clientId,
            'schedule_slot_id' => $scheduleSlotId,
            'price' => $price,
        ], $clientId, $slot->tenantId);

        return $saved;
    }

    public function confirmBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \InvalidArgumentException('Booking not found');
        }

        if (!$booking->canConfirm()) {
            throw new \RuntimeException('Cannot confirm this booking');
        }

        $confirmed = $booking->confirm();
        $saved = $this->bookingRepository->save($confirmed);

        $this->logUpdated('Booking', $saved->id, [
            'status' => $confirmed->status->value,
        ], $booking->clientId, $booking->tenantId);

        return $saved;
    }

    public function cancelBooking(int $bookingId, ?string $reason = null): Booking
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \InvalidArgumentException('Booking not found');
        }

        if (!$booking->canCancel()) {
            throw new \RuntimeException('Cannot cancel this booking');
        }

        $cancelled = $booking->cancel($reason);
        $saved = $this->bookingRepository->save($cancelled);

        $this->logAction('booking_cancelled', 'Booking', $saved->id, [
            'reason' => $reason,
        ], $booking->clientId, $booking->tenantId);

        $slot = $this->slotRepository->findById($booking->scheduleSlotId);
        if ($slot) {
            $updatedSlot = $slot->decrementBookedCount();
            $this->slotRepository->save($updatedSlot);
        }

        return $saved;
    }

    public function checkInClient(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \InvalidArgumentException('Booking not found');
        }

        if (!$booking->canCheckIn()) {
            throw new \RuntimeException('Cannot check in for this booking');
        }

        $checkedIn = $booking->checkIn();
        $this->bookingRepository->save($checkedIn);

        if ($booking->membershipId) {
            $membership = $this->membershipRepository->findById($booking->membershipId);
            if ($membership && $membership->isActive()) {
                $updatedMembership = $membership->useVisit();
                $this->membershipRepository->save($updatedMembership);
            }
        }

        $client = $this->clientRepository->findById($booking->clientId);
        if ($client) {
            $updatedClient = $client->incrementVisits();
            $this->clientRepository->save($updatedClient);
        }

        return $checkedIn;
    }

    public function completeSession(int $scheduleSlotId, ?string $trainerNotes = null): WorkoutSession
    {
        $slot = $this->slotRepository->findById($scheduleSlotId);
        if (!$slot) {
            throw new \InvalidArgumentException('Schedule slot not found');
        }

        $existingSession = $this->sessionRepository->findByScheduleSlotId($scheduleSlotId);
        if ($existingSession) {
            if ($trainerNotes) {
                $updatedSession = $existingSession->addTrainerNotes($trainerNotes);
                return $this->sessionRepository->save($updatedSession);
            }
            return $existingSession;
        }

        $bookings = $this->bookingRepository->findByScheduleSlotId($scheduleSlotId);
        $actualParticipants = count(array_filter($bookings, fn (Booking $b) => $b->status === BookingStatus::CHECKED_IN || $b->status === BookingStatus::COMPLETED));

        $session = WorkoutSession::create(
            tenantId: $slot->tenantId,
            scheduleSlotId: $scheduleSlotId,
            trainerId: $slot->trainerId,
            venueId: $slot->venueId,
            workoutTypeId: $slot->workoutTypeId,
            startTime: $slot->startTime,
            endTime: $slot->endTime,
            actualParticipants: $actualParticipants,
        );

        if ($trainerNotes) {
            $session = $session->addTrainerNotes($trainerNotes);
        }

        return $this->sessionRepository->save($session);
    }

    public function getAvailableSlots(int $tenantId, CarbonImmutable $date): array
    {
        return $this->slotRepository->findAvailableSlots($tenantId, $date);
    }

    public function getTrainerSchedule(int $trainerId, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return $this->slotRepository->findByTrainerId($trainerId->tenantId ?? 0, $trainerId);
    }

    public function getVenueOccupancy(int $venueId, CarbonImmutable $date): float
    {
        $slots = $this->slotRepository->findByVenueId(0, $venueId);
        if (empty($slots)) {
            return 0.0;
        }

        $totalCapacity = array_sum(array_map(fn (ScheduleSlot $s) => $s->capacity, $slots));
        $totalBooked = array_sum(array_map(fn (ScheduleSlot $s) => $s->bookedCount, $slots));

        if ($totalCapacity === 0) {
            return 0.0;
        }

        return ($totalBooked / $totalCapacity) * 100;
    }
}
