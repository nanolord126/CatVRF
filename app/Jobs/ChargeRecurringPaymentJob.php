<?php

declare(strict_types=1);

namespace App\Jobs;

use Modules\Supermarket\Infrastructure\Models\Subscription;
use Modules\Supermarket\Infrastructure\Models\SupermarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ChargeRecurringPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 300;

    public function __construct(
        public Subscription $subscription,
        public SupermarketOrder $order,
    ) {
        $this->onQueue('supermarket-high');
    }

    public function handle(): void
    {
        try {
            $paymentAdapter = app(\Modules\Payment\Application\Services\PaymentServiceAdapter::class);

            $result = $paymentAdapter->chargeRecurring(
                customerKey: (string) $this->subscription->buyer_id,
                amount: $this->order->total_amount,
                orderId: (string) $this->order->id,
                description: "Подписка #{$this->subscription->id}",
            );

            if ($result['success'] ?? false) {
                $this->order->update(['status' => 'paid']);

                Log::info('Recurring payment successful', [
                    'subscription_id' => $this->subscription->id,
                    'order_id' => $this->order->id,
                    'amount' => $this->order->total_amount,
                ]);
            } else {
                $this->handlePaymentFailure($result['message'] ?? 'Payment failed');
            }
        } catch (\Exception $e) {
            Log::error('Recurring payment job failed', [
                'subscription_id' => $this->subscription->id,
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->subscription->update(['status' => 'paused']);
                Log::warning('Subscription paused due to payment failure', [
                    'subscription_id' => $this->subscription->id,
                ]);
            }

            throw $e;
        }
    }

    private function handlePaymentFailure(string $reason): void
    {
        $this->order->update(['status' => 'payment_failed']);

        Log::error('Recurring payment failed', [
            'subscription_id' => $this->subscription->id,
            'order_id' => $this->order->id,
            'reason' => $reason,
        ]);

        if ($this->attempts() >= $this->tries) {
            $this->subscription->update(['status' => 'paused']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Recurring payment job permanently failed', [
            'subscription_id' => $this->subscription->id,
            'order_id' => $this->order->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
