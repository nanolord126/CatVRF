<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Payment\Domain\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class NotifyOnPaymentFailure implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $errorMessage = $event->errorMessage;

        Log::warning('Payment failed notification', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'error' => $errorMessage,
        ]);

        // Send notification to guest/customer
        $this->notifyGuest($payment, $errorMessage);

        // Send notification to venue owner/manager
        $this->notifyVenue($payment, $errorMessage);
    }

    private function notifyGuest(\Modules\Payment\Domain\Entities\Payment $payment, ?string $errorMessage): void
    {
        try {
            // Implement notification logic based on payable type
            // This would integrate with notification service (Telegram, VK, Email, etc.)
            Log::info('Guest notification sent for failed payment', [
                'payment_id' => $payment->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to notify guest', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyVenue(\Modules\Payment\Domain\Entities\Payment $payment, ?string $errorMessage): void
    {
        try {
            // Implement notification to venue staff
            Log::info('Venue notification sent for failed payment', [
                'payment_id' => $payment->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to notify venue', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
