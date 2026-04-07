<?php declare(strict_types=1);

namespace App\Domains\Education\KidsCenters\Services;

use App\Domains\Education\KidsCenters\Models\KidsBooking;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class KidsCentersService
{
    private const COMMISSION_RATE = 0.14;
    private const RATE_LIMIT_KEY = 'kids:book:';
    private const RATE_LIMIT_MAX = 20;
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
     * Создать бронирование детского центра.
     */
    public function createBooking(
        int $centerId,
        string $bookingDate,
        int $durationHours,
        int $kidsCount,
        int $priceKopecks,
        string $correlationId = '',
    ): KidsBooking {
        $correlationId = $correlationId ?: (string) Str::uuid();
        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'kids_booking',
            amount: $priceKopecks,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($centerId, $bookingDate, $durationHours, $kidsCount, $priceKopecks, $correlationId, $userId): KidsBooking {
            $payoutKopecks = $priceKopecks - (int) ($priceKopecks * self::COMMISSION_RATE);

            $booking = KidsBooking::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => tenant()->id,
                'center_id' => $centerId,
                'parent_id' => $userId,
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'total_kopecks' => $priceKopecks,
                'payout_kopecks' => $payoutKopecks,
                'payment_status' => 'pending',
                'booking_date' => $bookingDate,
                'duration_hours' => $durationHours,
                'kids_count' => $kidsCount,
                'tags' => ['kids_center' => true],
            ]);

            $this->audit->log(
                action: 'kids_booking_created',
                subjectType: KidsBooking::class,
                subjectId: $booking->id,
                old: [],
                new: $booking->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Kids center booking created', [
                'booking_id' => $booking->id,
                'correlation_id' => $correlationId,
            ]);

            return $booking;
        });
    }

    /**
     * Завершить бронирование и выплатить центру.
     */
    public function completeBooking(int $bookingId, string $correlationId = ''): KidsBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): KidsBooking {
            $booking = KidsBooking::findOrFail($bookingId);

            if ($booking->payment_status !== 'completed') {
                throw new \RuntimeException('Booking payment not completed', 400);
            }

            $booking->update([
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->wallet->credit(
                walletId: (int) $booking->tenant_id,
                amount: $booking->payout_kopecks,
                reason: 'education_' . strtolower('PAYOUT'),
                correlationId: $correlationId,
            );

            $this->audit->log(
                action: 'kids_booking_completed',
                subjectType: KidsBooking::class,
                subjectId: $booking->id,
                old: ['status' => 'pending_payment'],
                new: ['status' => 'completed'],
                correlationId: $correlationId,
            );

            return $booking;
        });
    }

    /**
     * Отменить бронирование и вернуть оплату.
     */
    public function cancelBooking(int $bookingId, string $correlationId = ''): KidsBooking
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($bookingId, $correlationId): KidsBooking {
            $booking = KidsBooking::findOrFail($bookingId);

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
                walletId: (int) $booking->tenant_id,
                amount: $booking->total_kopecks,
                reason: 'education_' . strtolower('REFUND'),
                correlationId: $correlationId,
            );
            }

            $this->audit->log(
                action: 'kids_booking_cancelled',
                subjectType: KidsBooking::class,
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
    public function getBooking(int $bookingId): KidsBooking
    {
        return KidsBooking::findOrFail($bookingId);
    }

    /**
     * Получить список бронирований родителя.
     */
    public function getUserBookings(int $parentId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return KidsBooking::where('parent_id', $parentId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
