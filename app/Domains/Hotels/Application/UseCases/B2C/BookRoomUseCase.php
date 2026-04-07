<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Application\UseCases\B2C;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Hotels\Domain\Repositories\BookingRepositoryInterface;
use App\Domains\Hotels\Domain\Repositories\RoomRepositoryInterface;
use App\Domains\Hotels\Domain\Entities\Booking;
use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Domain\Enums\BookingStatus;
use App\Domains\Hotels\Domain\Events\BookingConfirmed;
use App\Services\PaymentGatewayInterface;
use App\Services\FraudControlService;
use Illuminate\Support\Str;
use Carbon\Carbon;

final class BookRoomUseCase
{
    public function __construct(private readonly BookingRepositoryInterface $bookingRepository,
        private readonly RoomRepositoryInterface $roomRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {
    }

    public function __invoke(
        string $hotelId,
        string $roomId,
        int $userId,
        string $checkInDate,
        string $checkOutDate,
        ?string $correlationId = null
    ): BookingId {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);

        $this->db->beginTransaction();

        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'book_room', amount: 0, correlationId: $correlationId ?? '');

            $room = $this->roomRepository->find(RoomId::fromString($roomId));

            if (!$room || !$room->isAvailable()) {
                throw new \DomainException('Room not available.');
            }

            $existingBooking = $this->bookingRepository->findByRoomAndDateRange(
                $room->getId(),
                $checkIn,
                $checkOut
            );

            if ($existingBooking) {
                throw new \DomainException('Room is already booked for this period.');
            }

            $totalPrice = $this->calculatePrice($roomId, $checkIn, $checkOut);

            $booking = new Booking(
                id: BookingId::random(),
                hotelId: HotelId::fromString($hotelId),
                roomId: $room->getId(),
                userId: $userId,
                checkInDate: $checkIn,
                checkOutDate: $checkOut,
                totalPrice: $totalPrice,
                status: BookingStatus::PENDING,
                correlationId: $correlationId
            );

            $this->bookingRepository->save($booking);

            $paymentResult = $this->paymentGateway->initPayment(
                amount: $totalPrice,
                description: "Booking for room {$roomId}",
                correlationId: $correlationId,
                hold: true
            );

            if (!$paymentResult->isSuccessful()) {
                throw new \RuntimeException('Payment failed.');
            }

            $booking->confirm();
            $this->bookingRepository->save($booking);

            $this->db->commit();

            BookingConfirmed::dispatch($booking->getId(), $booking->getTotalPrice(), $booking->getUserId(), $correlationId);

            $this->logger->info('Room booked successfully', [
                'booking_id' => $booking->getId()->toString(),
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);

            return $booking->getId();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            $this->logger->error('Failed to book room', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    private function calculatePrice(string $roomId, Carbon $checkIn, Carbon $checkOut): int
    {
        $room = $this->roomRepository->find(RoomId::fromString($roomId));
        $days = $checkOut->diffInDays($checkIn);

        return $room->getPricePerNight() * $days;
    }
}
