<?php declare(strict_types=1);

namespace App\Domains\Medical\Listeners;

use App\Domains\Medical\Events\AppointmentBooked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeductAppointmentCommissionListener implements ShouldQueue
{
    public function handle(AppointmentBooked $event): void
    {
        try {
            DB::transaction(function () use ($event) {
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

                Log::channel('audit')->info('Medical appointment commission deducted', [
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'commission_amount' => $commission,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to deduct appointment commission', [
                'appointment_id' => $event->appointment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
