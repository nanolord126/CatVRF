<?php declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use App\Models\PaymentTransaction;
use App\Services\Payment\Gateways\PaymentGatewayInterface;
use App\Services\Payment\Gateways\TinkoffGateway;
use App\Services\Payment\Gateways\SberGateway;
use App\Services\Payment\Gateways\TochkaGateway;
use App\Services\Wallet\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Process Capture Job
 * 
 * Handles payment capture (finalizing a held payment) asynchronously.
 * Calls external gateway and updates wallet balance on success.
 */
final class ProcessCaptureJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public $backoff = [5, 15, 30];

    public function __construct(
        public readonly string $paymentUuid,
        public readonly ?int $amount,
        public readonly string $correlationId,
    ) {
        $this->onQueue('payments-high-priority');
    }

    public function uniqueId(): string
    {
        return "capture:{$this->paymentUuid}";
    }

    public function handle(
        TinkoffGateway $tinkoff,
        SberGateway $sber,
        TochkaGateway $tochka,
        WalletService $walletService,
    ): void {
        $payment = PaymentTransaction::where('uuid', $this->paymentUuid)->firstOrFail();

        Log::channel('audit')->info('Processing capture job', [
            'correlation_id' => $this->correlationId,
            'payment_uuid' => $this->paymentUuid,
            'amount' => $this->amount ?? $payment->hold_amount,
        ]);

        if ($payment->status !== PaymentTransaction::STATUS_AUTHORIZED) {
            Log::channel('audit')->warning('Payment not in authorized status, skipping capture', [
                'correlation_id' => $this->correlationId,
                'payment_uuid' => $this->paymentUuid,
                'current_status' => $payment->status,
            ]);
            return;
        }

        $gateway = match($payment->provider) {
            'tinkoff' => $tinkoff,
            'sber' => $sber,
            'tochka' => $tochka,
            default => throw new Exception("Unknown payment provider: {$payment->provider}"),
        };

        try {
            // Call gateway capture
            $result = $gateway->capture($payment);

            if ($result) {
                $captureAmount = $this->amount ?? $payment->hold_amount;

                // Credit wallet (async to avoid holding DB transaction)
                $walletService->credit(
                    tenantId: $payment->tenant_id,
                    amount: $captureAmount,
                    type: 'payment_capture',
                    sourceId: $payment->id,
                    correlationId: $this->correlationId,
                    reason: "Payment capture {$payment->uuid}",
                    sourceType: 'payment',
                    walletId: $payment->wallet_id,
                );

                $payment->update([
                    'status' => PaymentTransaction::STATUS_CAPTURED,
                    'captured_amount' => $captureAmount,
                    'captured_at' => now(),
                    'correlation_id' => $this->correlationId,
                ]);

                Log::channel('audit')->info('Capture processed successfully', [
                    'correlation_id' => $this->correlationId,
                    'payment_uuid' => $this->paymentUuid,
                    'captured_amount' => $captureAmount,
                ]);
            }

        } catch (Exception $e) {
            if ($this->attempts() >= $this->tries) {
                Log::channel('audit')->error('Capture failed after all retries', [
                    'correlation_id' => $this->correlationId,
                    'payment_uuid' => $this->paymentUuid,
                    'error' => $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }
}
