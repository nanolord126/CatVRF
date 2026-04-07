<?php declare(strict_types=1);

namespace App\Domains\Pet\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductBoardingCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(BoardingReservationCreated $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $clinic = $event->reservation->clinic;
                    $wallet = $clinic->owner->wallet;

                    $wallet->lockForUpdate();
                    $commissionAmount = (int)($event->reservation->total_amount * 0.14 * 100);

                    $wallet->decrement('current_balance', $commissionAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $event->reservation->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionAmount,
                        'status' => 'completed',
                        'reference_type' => 'pet_boarding',
                        'reference_id' => $event->reservation->id,
                        'correlation_id' => $event->correlationId,
                        'metadata' => [
                            'clinic_id' => $clinic->id,
                            'reservation_number' => $event->reservation->reservation_number,
                            'pet_name' => $event->reservation->pet_name,
                        ],
                    ]);

                    $this->logger->info('Pet boarding commission deducted', [
                        'reservation_id' => $event->reservation->id,
                        'clinic_id' => $clinic->id,
                        'amount' => $commissionAmount / 100,
                        'correlation_id' => $event->correlationId,
                        'wallet_id' => $wallet->id,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to deduct boarding commission', [
                    'reservation_id' => $event->reservation->id,
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
