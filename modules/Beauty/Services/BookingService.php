<?php

namespace Modules\Beauty\Services;

use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Modules\Beauty\Enums\BookingStatus;
use Modules\Beauty\Models\Booking;
use Modules\Beauty\Models\Service;
use Psr\Log\LoggerInterface;

class BookingService
{
    public function __construct(
        private DatabaseManager $database,
        private LoggerInterface $logger,
    ) {}

    public function createBooking(
        Service $service,
        int $customerId,
        string $scheduledAt,
        ?string $notes = null,
    ): Booking {
        if (!$service->is_active) {
            throw new Exception('Услуга неактивна');
        }

        if (strtotime($scheduledAt) <= time()) {
            throw new Exception('Дата бронирования не может быть в прошлом');
        }

        $correlationId = Str::uuid();

        try {
            $booking = $this->database->transaction(function () use (
                $service,
                $customerId,
                $scheduledAt,
                $notes,
                $correlationId,
            ) {
                $booking = Booking::create([
                    'service_id' => $service->id,
                    'salon_id' => $service->salon_id,
                    'tenant_id' => $service->tenant_id,
                    'customer_id' => $customerId,
                    'scheduled_at' => $scheduledAt,
                    'status' => BookingStatus::PENDING,
                    'notes' => $notes,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Booking created', [
                    'booking_id' => $booking->id,
                    'correlation_id' => $correlationId,
                    'salon_id' => $service->salon_id,
                ]);

                return $booking;
            });

            return $booking;
        } catch (Exception $e) {
            $this->logger->error('Failed to create booking', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function confirmBooking(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::PENDING) {
            throw new Exception('Бронирование может быть подтверждено только из статуса "Ожидание"');
        }

        return $this->database->transaction(function () use ($booking) {
            $booking->markAsConfirmed();

            $this->logger->info('Booking confirmed', [
                'booking_id' => $booking->id,
                'correlation_id' => $booking->correlation_id,
            ]);

            return $booking;
        });
    }

    public function completeBooking(Booking $booking): Booking
    {
        if (!in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::PENDING])) {
            throw new Exception('Невозможно завершить бронирование в текущем статусе');
        }

        return $this->database->transaction(function () use ($booking) {
            $booking->markAsCompleted();

            $this->logger->info('Booking completed', [
                'booking_id' => $booking->id,
                'correlation_id' => $booking->correlation_id,
            ]);

            return $booking;
        });
    }

    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        if ($booking->status === BookingStatus::COMPLETED) {
            throw new Exception('Завершённое бронирование не может быть отменено');
        }

        return $this->database->transaction(function () use ($booking, $reason) {
            $booking->markAsCancelled();
            if ($reason) {
                $booking->update(['notes' => $reason]);
            }

            $this->logger->info('Booking cancelled', [
                'booking_id' => $booking->id,
                'correlation_id' => $booking->correlation_id,
                'reason' => $reason,
            ]);

            return $booking;
        });
    }
}
