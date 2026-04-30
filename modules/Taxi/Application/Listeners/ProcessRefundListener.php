<?php

declare(strict_types=1);

namespace Modules\Taxi\Listeners;

use Modules\Taxi\Events\TaxiRideCancelledEvent;
use Illuminate\Log\LogManager;
use App\Jobs\ProcessTaxiRefundJob;

/**
 * Listener for TaxiRideCancelledEvent.
 * Triggers refund processing if applicable.
 */
final readonly class ProcessRefundListener
{
    public function __construct(
        private readonly LogManager $log,
    ) {}

    public function handle(TaxiRideCancelledEvent $event): void
    {
        if ($event->ride->payment_id === null) {
            $this->log->channel('audit')->info('No payment to refund', [
                'ride_id' => $event->ride->id,
                'correlation_id' => $event->correlationId,
            ]);

            return;
        }

        $cancellationFee = $event->calculateCancellationFee();
        $refundAmount = ($event->ride->final_price_kopeki / 100) - $cancellationFee;

        if ($refundAmount > 0) {
            $this->log->channel('audit')->info('Refund processing triggered', [
                'ride_id' => $event->ride->id,
                'refund_amount_rubles' => $refundAmount,
                'cancellation_fee_rubles' => $cancellationFee,
                'correlation_id' => $event->correlationId,
            ]);

            ProcessTaxiRefundJob::dispatch(
                $event->ride->payment_id,
                (int) ceil($refundAmount * 100),
                $event->reason,
                $event->correlationId,
            )->onQueue('refunds');
        }
    }
}
