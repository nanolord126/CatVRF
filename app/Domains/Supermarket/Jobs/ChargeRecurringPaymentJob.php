<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Jobs;

use App\Domains\Supermarket\Models\Subscription;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Services\PaymentServiceAdapter;
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
    public int $backoff = 10;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly SupermarketOrder $order
    ) {}

    public function handle(PaymentServiceAdapter $paymentService): void
    {
        try {
            $result = $paymentService->chargeRecurring(
                customerKey: (string) $this->subscription->buyer_id,
                amount: $this->order->total_amount,
                orderId: (string) $this->order->id,
                description: "Подписка #{$this->subscription->id}"
            );

            if ($result['success'] ?? false) {
                $this->order->update(['status' => 'paid']);
                
                Log::info('Recurring payment successful', [
                    'subscription_id' => $this->subscription->id,
                    'order_id' => $this->order->id,
                    'amount' => $this->order->total_amount,
                ]);
            } else {
                $this->handleFailedPayment($result['message'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Recurring payment job failed', [
                'subscription_id' => $this->subscription->id,
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->subscription->update(['status' => 'paused']);
                Log::warning('Subscription paused due to payment failures', [
                    'subscription_id' => $this->subscription->id,
                ]);
            }

            $this->release($this->backoff * $this->attempts());
        }
    }

    private function handleFailedPayment(string $reason): void
    {
        Log::warning('Recurring payment failed', [
            'subscription_id' => $this->subscription->id,
            'order_id' => $this->order->id,
            'reason' => $reason,
        ]);

        $this->subscription->update(['status' => 'paused']);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ChargeRecurringPaymentJob failed permanently', [
            'subscription_id' => $this->subscription->id,
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        $this->subscription->update(['status' => 'paused']);
    }
}
