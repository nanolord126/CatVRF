<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Models\Appointment;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessAppointmentPaymentJob implements ShouldQueue
{
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

        $this->db->transaction(function () use ($appointment, $walletService): void {
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

            $this->log->channel('audit')->info('Payment processed', [
                'appointment_id' => $appointment->id,
                'amount' => $appointment->price,
                'net_amount' => $netAmount,
                'status' => 'paid',
                'correlation_id' => $this->correlationId,
            ]);
        });
    }
}
