<?php

declare(strict_types=1);

namespace App\Domains\CarRental\Services;

use App\Domains\CarRental\Models\RentalBooking;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Сервис аренды автомобилей.
 *
 * Управляет жизненным циклом бронирований: создание, подтверждение, отмена.
 * Все финансовые операции проходят через WalletService.
 */
final readonly class CarRentalService
{
    private const VERSION = '1.0.0';
    private const MAX_RETRIES = 3;
    private const CACHE_TTL = 3600;
    private const COMMISSION_RATE = 0.14;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Создать бронирование авто.
     */
    public function createBooking(
        int $carId,
        string $pickupDate,
        string $returnDate,
        string $correlationId = '',
    ): RentalBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = $this->guard->id() ?? 0;

        $key = "rental:book:{$userId}";
        if ($this->rateLimiter->tooManyAttempts($key, 9)) {
            throw new \RuntimeException('Too many booking attempts', 429);
        }
        $this->rateLimiter->hit($key, 3600);

        return $this->db->transaction(function () use ($carId, $pickupDate, $returnDate, $correlationId, $userId) {
            $this->fraud->check(
                userId: $userId,
                operationType: 'car_rental',
                amount: 0,
                correlationId: $correlationId,
            );

            $pickup = \Carbon\Carbon::parse($pickupDate);
            $return = \Carbon\Carbon::parse($returnDate);
            $days = max(1, (int) $pickup->diffInDays($return));
            $total = $days * 500000;
            $payout = $total - (int) ($total * self::COMMISSION_RATE);

            $booking = RentalBooking::create([
                'uuid'           => Str::uuid()->toString(),
                'tenant_id'      => tenant()->id,
                'car_id'         => $carId,
                'renter_id'      => $userId,
                'correlation_id' => $correlationId,
                'status'         => 'pending_payment',
                'total_kopecks'  => $total,
                'payout_kopecks' => $payout,
                'payment_status' => 'pending',
                'pickup_date'    => $pickupDate,
                'return_date'    => $returnDate,
                'days_count'     => $days,
                'tags'           => ['rental' => true],
            ]);

            $this->logger->info('Car rental booking created', [
                'booking_id'     => $booking->id,
                'days'           => $days,
                'total_kopecks'  => $total,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Подтвердить и активировать бронирование (после оплаты).
     */
    public function completeBooking(int $bookingId, string $correlationId = ''): RentalBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = RentalBooking::findOrFail($bookingId);

            if ($booking->payment_status !== 'completed') {
                throw new \RuntimeException('Booking not paid', 400);
            }

            $booking->update([
                'status'         => 'active',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) tenant()->id,
                amount: $booking->payout_kopecks,
                reason: 'car_rental_payout',
                correlationId: $correlationId,
            );

            return $booking;
        });
    }

    /**
     * Отменить бронирование и вернуть средства при необходимости.
     */
    public function cancelBooking(int $bookingId, string $correlationId = ''): RentalBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId) {
            $booking = RentalBooking::findOrFail($bookingId);

            if ($booking->status === 'active') {
                throw new \RuntimeException('Cannot cancel active booking', 400);
            }

            $booking->update([
                'status'         => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($booking->payment_status === 'completed') {
                $this->wallet->credit(
                    walletId: (int) tenant()->id,
                    amount: $booking->total_kopecks,
                    reason: 'car_rental_refund',
                    correlationId: $correlationId,
                );
            }

            return $booking;
        });
    }

    /**
     * Получить бронирование по id.
     */
    public function getBooking(int $bookingId): RentalBooking
    {
        return RentalBooking::findOrFail($bookingId);
    }

    /**
     * Получить бронирования пользователя (10 последних).
     */
    public function getUserBookings(int $renterId): \Illuminate\Support\Collection
    {
        return RentalBooking::where('renter_id', $renterId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * Идентификатор компонента для логирования.
     */
    private function getComponentIdentifier(): string
    {
        return static::class . '@' . self::VERSION;
    }

    /**
     * Повтор операции при ошибке.
     */
    private function handleError(\Throwable $exception, int $attempt = 1): bool
    {
        if ($attempt >= self::MAX_RETRIES) {
            return false;
        }

        return true;
    }
}
