<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Karaoke\Services;

use App\Domains\EventPlanning\Karaoke\Models\KaraokeBooking;
use App\Domains\Wallet\Enums\BalanceTransactionType;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class KaraokeService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'karaoke:book:';
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
     * Создать бронирование караоке-комнаты.
     */
    public function createBooking(
        int $clubId,
        string $bookingDate,
        int $durationHours,
        string $roomNumber,
        int $priceKopecks,
        string $correlationId = '',
    ): KaraokeBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'karaoke_booking',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($clubId, $bookingDate, $durationHours, $roomNumber, $priceKopecks, $correlationId, $userId): KaraokeBooking {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $booking = KaraokeBooking::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'club_id' => $clubId,
                'client_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'booking_date' => $bookingDate,
                'duration_hours' => $durationHours,
                'room_number' => $roomNumber,
                'tags' => ['karaoke' => true],
            ]);

            $this->audit->log(
                action: 'karaoke_booking_created',
                subjectType: KaraokeBooking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Karaoke booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Завершить бронирование и выплатить клубу.
     */
    public function completeBooking(int $bookingId, string $correlationId = ''): KaraokeBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): KaraokeBooking {
            $booking = KaraokeBooking::findOrFail($bookingId);

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
                action: 'karaoke_booking_completed',
                subjectType: KaraokeBooking::class,
                subjectId: $booking->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $booking;
        });
    }

    /**
     * Отменить бронирование караоке.
     */
    public function cancelBooking(int $bookingId, string $correlationId = ''): KaraokeBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): KaraokeBooking {
            $booking = KaraokeBooking::findOrFail($bookingId);

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
                action: 'karaoke_booking_cancelled',
                subjectType: KaraokeBooking::class,
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
    public function getBooking(int $bookingId): KaraokeBooking
    {
        return KaraokeBooking::findOrFail($bookingId);
    }

    /**
     * Получить список бронирований клиента.
     */
    public function getUserBookings(int $clientId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return KaraokeBooking::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
