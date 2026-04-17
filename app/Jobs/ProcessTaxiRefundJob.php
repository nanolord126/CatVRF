<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use App\Models\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Services\AuditService;

/**
 * Job for processing taxi ride refund after cancellation.
 * Runs asynchronously on the refunds queue.
 */
final class ProcessTaxiRefundJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public int $paymentId,
        public int $refundAmountKopeki,
        public string $reason,
        public string $correlationId,
        private readonly LoggerInterface $logger,
    ) {
        $this->tries = 3;
        $this->timeout = 120;
    }

    public function handle(PaymentService $payment, AuditService $audit): void
    {
        $paymentTransaction = PaymentTransaction::where('id', $this->paymentId)->firstOrFail();

        $this->logger->channel('audit')->info('Processing taxi refund', [
            'payment_id' => $this->paymentId,
            'refund_amount_rubles' => $this->refundAmountKopeki / 100,
            'reason' => $this->reason,
            'correlation_id' => $this->correlationId,
        ]);

        $payment->refundPayment($paymentTransaction, $this->refundAmountKopeki, $this->correlationId);

        $audit->record(
            action: 'taxi_refund_processed',
            subjectType: PaymentTransaction::class,
            subjectId: $this->paymentId,
            newValues: [
                'refund_amount_kopeki' => $this->refundAmountKopeki,
                'refund_reason' => $this->reason,
            ],
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('Taxi refund processing failed', [
            'payment_id' => $this->paymentId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
