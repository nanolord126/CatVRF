<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;
use Modules\Hotels\Application\Services\BookingService;
use Modules\Hotels\Infrastructure\Models\BookingModel;

final class UpdateBookingOnPaymentFailure
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function handle(PaymentFailed $event): void
    {
        if ($event->payment->payable_type !== BookingModel::class) {
            return;
        }

        $booking = BookingModel::find($event->payment->payable_id);
        if (!$booking) {
            Log::warning('Booking not found for payment', [
                'payment_id' => $event->payment->id,
                'booking_id' => $event->payment->payable_id,
            ]);
            return;
        }

        try {
            $this->bookingService->updatePaymentStatus(
                $booking->id,
                'failed',
                0,
            );

            Log::channel('audit')->info('Booking payment status updated to failed', [
                'booking_id' => $booking->id,
                'payment_id' => $event->payment->id,
                'error_message' => $event->errorMessage,
                'tenant_id' => $event->payment->tenant_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update booking payment status', [
                'booking_id' => $booking->id,
                'payment_id' => $event->payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
