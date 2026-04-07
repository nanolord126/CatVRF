<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\EventHalls\Services;

use App\Domains\EventPlanning\EventHalls\Models\HallBooking;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class EventHallsService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'halls:book:';
    private const RATE_LIMIT_MAX = 10;
    private const RATE_LIMIT_DECAY = 3600;

    public function __construct(
        private FraudControlService $fraud,
        private WalletService $wallet,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать бронирование зала.
     */
    public function createBooking(
        int $hallId,
        string $bookingDate,
        int $durationHours,
        string $eventType,
        int $priceKopecks,
        string $correlationId = '',
    ): HallBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hall_booking',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($hallId, $bookingDate, $durationHours, $eventType, $priceKopecks, $correlationId, $userId): HallBooking {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $booking = HallBooking::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'hall_id' => $hallId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'booking_date' => $bookingDate,
                'duration_hours' => $durationHours,
                'event_type' => $eventType,
                'tags' => ['event_halls' => true],
            ]);

            $this->audit->log(
                action: 'hall_booking_created',
                subjectType: HallBooking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Event hall booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Завершить бронирование и выплатить владельцу зала.
     */
    public function completeBooking(int $bookingId, string $correlationId = ''): HallBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): HallBooking {
            $booking = HallBooking::findOrFail($bookingId);

            if ($booking->payment_status !== 'completed') {
                throw new \RuntimeException('Booking payment not completed', 400);
            }

            $booking->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: $booking->tenant_id,
                amount: $booking->payout_kopecks,
                type: BalanceTransactionType::PAYOUT,
                correlationId: $correlationId,
                metadata: ['booking_id' => $booking->id],
            );

            $this->audit->log(
                action: 'hall_booking_completed',
                subjectType: HallBooking::class,
                subjectId: $booking->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $booking;
        });
    }

    /**
     * Отменить бронирование зала.
     */
    public function cancelBooking(int $bookingId, string $correlationId = ''): HallBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): HallBooking {
            $booking = HallBooking::findOrFail($bookingId);

            if ($booking->status === 'completed') {
                throw new \RuntimeException('Cannot cancel completed booking', 400);
            }

            $previousStatus = $booking->payment_status;

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'correlation_id' => $correlationId,
            ]);

            if ($previousStatus === 'completed') {
                $this->wallet->credit(
                    walletId: $booking->tenant_id,
                    amount: $booking->total_kopecks,
                    type: BalanceTransactionType::REFUND,
                    correlationId: $correlationId,
                    metadata: ['booking_id' => $booking->id],
                );
            }

            $this->audit->log(
                action: 'hall_booking_cancelled',
                subjectType: HallBooking::class,
                subjectId: $booking->id,
                old: ['status' => $previousStatus],
                new: ['status' => 'cancelled'],
                correlationId: $correlationId,
            );

            return $booking;
        });
    }

    /**
     * Получить бронирование по идентификатору.
     */
    public function getBooking(int $bookingId): HallBooking
    {
        return HallBooking::findOrFail($bookingId);
    }

    /**
     * Получить список бронирований клиента.
     */
    public function getUserBookings(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return HallBooking::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
