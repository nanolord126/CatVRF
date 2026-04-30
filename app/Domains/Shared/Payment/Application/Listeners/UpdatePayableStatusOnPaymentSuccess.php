<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Payment\Domain\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class UpdatePayableStatusOnPaymentSuccess implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;
        $payableId = $payment->payableId;
        $payableType = $payment->payableType;

        if ($payableId === null || $payableType === null) {
            Log::warning('Payment has no payable', ['payment_id' => $payment->id]);
            return;
        }

        // Handle Order payment (Restaurant)
        if ($payableType === 'order') {
            $this->updateOrderStatus($payableId, $payment->amount);
        }
        // Handle Booking payment (Hotels)
        elseif ($payableType === 'booking') {
            $this->updateBookingStatus($payableId, $payment->amount);
        }
    }

    private function updateOrderStatus(int $orderId, float $amount): void
    {
        try {
            $order = \Modules\Restaurant\Domain\Entities\Order::find($orderId);
            if ($order === null) {
                Log::warning('Order not found for payment', ['order_id' => $orderId]);
                return;
            }

            // Check if payment covers full order amount
            if ($amount >= $order->totalAmount) {
                // Update to paid status
                $order->markPaid();
            } else {
                // Update to partially paid status
                $order->markPartiallyPaid();
            }

            Log::info('Order status updated after payment', [
                'order_id' => $orderId,
                'amount' => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update order status', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateBookingStatus(int $bookingId, float $amount): void
    {
        try {
            $booking = \Modules\Hotels\Domain\Entities\Booking::find($bookingId);
            if ($booking === null) {
                Log::warning('Booking not found for payment', ['booking_id' => $bookingId]);
                return;
            }

            // Check if payment covers full booking amount
            if ($amount >= $booking->totalAmount) {
                // Update to paid status
                $booking->markPaid();
            } else {
                // Update to prepaid status
                $booking->markPrepaid();
            }

            Log::info('Booking status updated after payment', [
                'booking_id' => $bookingId,
                'amount' => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update booking status', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
