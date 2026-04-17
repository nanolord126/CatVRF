<?php declare(strict_types=1);

namespace Modules\RealEstate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Services\PropertyBookingService;
use Illuminate\Support\Facades\Log;

final class ProcessBookingExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public int $bookingId,
    ) {}

    public function handle(PropertyBookingService $bookingService): void
    {
        $booking = PropertyBooking::find($this->bookingId);

        if ($booking === null) {
            Log::channel('audit')->warning('real_estate.booking.expiration.not_found', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        if ($booking->status !== BookingStatus::PENDING) {
            Log::channel('audit')->info('real_estate.booking.expiration.not_pending', [
                'booking_id' => $booking->id,
                'status' => $booking->status->value,
            ]);
            return;
        }

        if (!$booking->isHoldExpired()) {
            Log::channel('audit')->info('real_estate.booking.expiration.not_expired', [
                'booking_id' => $booking->id,
                'hold_until' => $booking->hold_until->toIso8601String(),
            ]);
            return;
        }

        try {
            $bookingService->cancelBooking(
                $booking->id,
                'Hold period expired',
                \Illuminate\Support\Str::uuid()->toString()
            );

            Log::channel('audit')->info('real_estate.booking.expiration.cancelled', [
                'booking_id' => $booking->id,
                'property_id' => $booking->property_id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->critical('real_estate.booking.expiration.error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            $this->release(300);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->critical('real_estate.booking.expiration.job_failed', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
