<?php declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Modules\Taxi\Models\TaxiRide;
use App\Services\Payment\PaymentService;
use App\Services\AuditService;

/**
 * Job for processing taxi ride payment.
 * Runs asynchronously on the payments queue.
 */
final class ProcessTaxiPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;
    public int $timeout;

    public function __construct(
        public int $rideId,
        public string $correlationId,
        private readonly LoggerInterface $logger,
    ) {
        $this->tries = 3;
        $this->timeout = 120;
    }

    public function handle(PaymentService $payment, AuditService $audit): void
    {
        $ride = TaxiRide::with(['passenger', 'driver'])->where('id', $this->rideId)->firstOrFail();

        $this->logger->channel('audit')->info('Processing taxi payment', [
            'ride_id' => $this->rideId,
            'passenger_id' => $ride->passenger_id,
            'final_price_rubles' => $ride->final_price_kopeki / 100,
            'correlation_id' => $this->correlationId,
        ]);

        $paymentTransaction = $payment->initPayment(
            amount: $ride->final_price_kopeki,
            tenantId: $ride->tenant_id,
            userId: $ride->passenger_id,
            paymentMethod: 'card',
            hold: false,
            idempotencyKey: "taxi_payment_{$this->rideId}",
            correlationId: $this->correlationId,
            metadata: [
                'ride_id' => $this->rideId,
                'ride_uuid' => $ride->uuid,
                'driver_id' => $ride->driver_id,
            ],
        );

        $ride->update(['payment_id' => $paymentTransaction->id]);

        $audit->record(
            action: 'taxi_payment_processed',
            subjectType: TaxiRide::class,
            subjectId: $this->rideId,
            newValues: [
                'payment_id' => $paymentTransaction->id,
                'amount_rubles' => $ride->final_price_kopeki / 100,
            ],
            correlationId: $this->correlationId,
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->logger->channel('audit')->error('Taxi payment processing failed', [
            'ride_id' => $this->rideId,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
