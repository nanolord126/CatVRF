<?php declare(strict_types=1);

namespace App\Domains\Pet\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductAppointmentCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(AppointmentBooked $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $clinic = $event->appointment->clinic;
                    $wallet = $clinic->owner->wallet;

                    $wallet->lockForUpdate();
                    $commissionAmount = (int)($event->appointment->price * 0.14 * 100);

                    $wallet->decrement('current_balance', $commissionAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $event->appointment->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => $commissionAmount,
                        'status' => 'completed',
                        'reference_type' => 'pet_appointment',
                        'reference_id' => $event->appointment->id,
                        'correlation_id' => $event->correlationId,
                        'metadata' => [
                            'clinic_id' => $clinic->id,
                            'appointment_number' => $event->appointment->appointment_number,
                            'pet_name' => $event->appointment->pet_name,
                        ],
                    ]);

                    $this->logger->info('Pet appointment commission deducted', [
                        'appointment_id' => $event->appointment->id,
                        'clinic_id' => $clinic->id,
                        'amount' => $commissionAmount / 100,
                        'correlation_id' => $event->correlationId,
                        'wallet_id' => $wallet->id,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to deduct appointment commission', [
                    'appointment_id' => $event->appointment->id,
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
