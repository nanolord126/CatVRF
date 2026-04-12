<?php

declare(strict_types=1);

/**
 * HotelPayoutJob — CatVRF 2026 Component.
 *
 * Handles asynchronous payout processing for completed hotel bookings.
 * Debits the platform wallet and credits the hotel tenant wallet
 * after a booking is marked as completed.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary
 *
 * @see https://catvrf.ru/docs/hotelpayoutjob
 */

namespace App\Domains\Hotels\Jobs;


use App\Domains\Hotels\HotelManagement\Models\HotelBooking;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class HotelPayoutJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        private readonly int $bookingId,
        private readonly string $correlationId,
    ) {}

    /**
     * Execute the job — process payout for a completed hotel booking.
     *
     * Validates the booking status, runs fraud check on the payout,
     * then credits the tenant wallet with the payout amount.
     */
    public function handle(
        WalletService $wallet,
        FraudControlService $fraud,
        LoggerInterface $logger,
    ): void {
        $correlationId = $this->correlationId ?: (string) Str::uuid();

        $booking = HotelBooking::findOrFail($this->bookingId);

        if ($booking->status !== 'completed') {
            $logger->warning('HotelPayoutJob skipped: booking not completed', [
                'booking_id' => $this->bookingId,
                'status' => $booking->status,
                'correlation_id' => $correlationId,
            ]);

            return;
        }

        if ($booking->payment_status !== 'completed') {
            $logger->warning('HotelPayoutJob skipped: payment not completed', [
                'booking_id' => $this->bookingId,
                'payment_status' => $booking->payment_status,
                'correlation_id' => $correlationId,
            ]);

            return;
        }

        $fraudResult = $fraud->check(
            userId: 0,
            operationType: 'hotel_payout',
            amount: $booking->payout_kopecks,
            correlationId: $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $logger->error('HotelPayoutJob blocked by fraud control', [
                'booking_id' => $this->bookingId,
                'amount' => $booking->payout_kopecks,
                'correlation_id' => $correlationId,
            ]);

            return;
        }

        $wallet->credit(
            (int) $booking->tenant_id,
            $booking->payout_kopecks,
            'hotel_payout',
            $correlationId,
        );

        $logger->info('HotelPayoutJob completed successfully', [
            'booking_id' => $this->bookingId,
            'payout_kopecks' => $booking->payout_kopecks,
            'tenant_id' => $booking->tenant_id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        report(new \RuntimeException(
            sprintf(
                'HotelPayoutJob failed [booking_id=%d, correlation_id=%s]: %s',
                $this->bookingId,
                $this->correlationId,
                $exception->getMessage(),
            ),
            previous: $exception,
        ));
    }
}
