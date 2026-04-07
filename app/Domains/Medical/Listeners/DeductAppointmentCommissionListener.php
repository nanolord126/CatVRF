<?php declare(strict_types=1);

namespace App\Domains\Medical\Listeners;



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
                    $appointment = $event->appointment;
                    $commission = $appointment->commission_amount;

                    if ($commission <= 0) return;

                    $wallet = \App\Models\Wallet::lockForUpdate()
                        ->where('tenant_id', $appointment->tenant_id)
                        ->firstOrFail();

                    $wallet->decrement('balance', (int) ($commission * 100));

                    \App\Models\BalanceTransaction::create([
                        'tenant_id' => $appointment->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'commission',
                        'amount' => (int) ($commission * 100),
                        'description' => "Commission for appointment #{$appointment->appointment_number}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    $this->logger->info('Medical appointment commission deducted', [
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $appointment->doctor_id,
                        'patient_id' => $appointment->patient_id,
                        'commission_amount' => $commission,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to deduct appointment commission', [
                    'appointment_id' => $event->appointment->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
