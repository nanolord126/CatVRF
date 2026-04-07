<?php declare(strict_types=1);

namespace App\Domains\Travel\Listeners;


use Psr\Log\LoggerInterface;
final class DeductTourBookingCommissionListener
{

    use InteractsWithQueue;
use App\Services\FraudControlService;

        public function __construct(public string $queue = 'travel',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function handle(TourBooked $event): void
        {
            try {
                $this->db->transaction(function () use ($event) {
                    $wallet = $event->booking->agency->owner->wallet;

                    if ($wallet === null) {
                        throw new \RuntimeException('Agency owner wallet not found');
                    }

                    $wallet->lockForUpdate();

                    $commissionInCents = (int)($event->booking->commission_amount * 100);

                    $wallet->decrement('current_balance', $commissionInCents);

                    BalanceTransaction::create([
                        'tenant_id' => $event->booking->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionInCents,
                        'description' => "Commission for tour booking #{$event->booking->booking_number}",
                        'reference_type' => 'travel_booking',
                        'reference_id' => $event->booking->id,
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Travel commission deducted', [
                        'booking_id' => $event->booking->id,
                        'booking_number' => $event->booking->booking_number,
                        'agency_id' => $event->booking->agency_id,
                        'commission_amount' => $event->booking->commission_amount,
                        'correlation_id' => $event->correlationId,
                        'wallet_id' => $wallet->id,
                        'timestamp' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Travel commission deduction failed', [
                    'booking_id' => $event->booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }
}
