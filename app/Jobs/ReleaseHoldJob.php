<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\PaymentTransaction;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReleaseHoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function handle(): void
    {
        try {
            Log::channel('audit')->info('ReleaseHoldJob started');

            // Найти все AUTHORIZED платежи с холдом, которые зависают > 24 часов
            $expiryTime = now()->subHours(24);

            $expiredPayments = PaymentTransaction::query()
                ->where('status', PaymentTransaction::STATUS_AUTHORIZED)
                ->where('hold', true)
                ->where('authorized_at', '<', $expiryTime)
                ->where('authorized_at', '!=', null)
                ->limit(100)
                ->get();

            Log::channel('audit')->info('Found expired holds', [
                'count' => $expiredPayments->count(),
                'expiry_time' => $expiryTime->toIso8601String(),
            ]);

            foreach ($expiredPayments as $payment) {
                $this->releasePaymentHold($payment);
            }

        } catch (Exception $e) {
            Log::channel('audit')->error('ReleaseHoldJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Выполнить release для одного платежа
     */
    private function releasePaymentHold(PaymentTransaction $payment): void
    {
        try {
            DB::transaction(function () use ($payment) {
                // Обновить платёж на CANCELLED (холд не был захвачен)
                $payment->update([
                    'status' => PaymentTransaction::STATUS_CANCELLED,
                    'failed_at' => now(),
                ]);

                // Если есть wallet — освободить холд
                if ($payment->wallet) {
                    $payment->wallet->decrement('hold_amount', $payment->hold_amount ?? $payment->amount);

                    Log::channel('audit')->info('Hold released', [
                        'payment_id' => $payment->id,
                        'wallet_id' => $payment->wallet->id,
                        'hold_amount' => $payment->hold_amount ?? $payment->amount,
                        'correlation_id' => $payment->correlation_id,
                    ]);
                }
            });

        } catch (Exception $e) {
            Log::channel('audit')->error('Failed to release hold', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $payment->correlation_id,
            ]);

            throw $e;
        }
    }
}
