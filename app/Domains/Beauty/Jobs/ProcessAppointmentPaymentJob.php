<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProcessAppointmentPaymentJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private readonly int $appointmentId,
            private readonly string $correlationId,
        ) {}

        public function handle(WalletService $walletService): void
        {
            $appointment = Appointment::with(['client', 'salon', 'master'])->findOrFail($this->appointmentId);

            DB::transaction(function () use ($appointment, $walletService): void {
                // Mark as paid and process wallet credit
                $appointment->update([
                    'payment_status' => 'paid',
                    'payment_captured_at' => now(),
                ]);

                // Credit salon/master wallet (after commission)
                $netAmount = (int)($appointment->price * (1 - ($appointment->commission_rate ?? 0.14)));

                if ($appointment->salon && $appointment->salon->wallet_id) {
                    $walletService->credit(
                        $appointment->salon->wallet_id,
                        $netAmount,
                        'appointment_payment',
                        $this->correlationId
                    );
                }

                Log::channel('audit')->info('Payment processed', [
                    'appointment_id' => $appointment->id,
                    'amount' => $appointment->price,
                    'net_amount' => $netAmount,
                    'status' => 'paid',
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }
}
