<?php declare(strict_types=1);

namespace App\Domains\Pet\Listeners;

use App\Domains\Pet\Events\AppointmentBooked;
use App\Models\BalanceTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DeductAppointmentCommissionListener implements ShouldQueue
{
    public function handle(AppointmentBooked $event): void
    {
        try {
            DB::transaction(function () use ($event) {
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

                Log::channel('audit')->info('Pet appointment commission deducted', [
                    'appointment_id' => $event->appointment->id,
                    'clinic_id' => $clinic->id,
                    'amount' => $commissionAmount / 100,
                    'correlation_id' => $event->correlationId,
                    'wallet_id' => $wallet->id,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to deduct appointment commission', [
                'appointment_id' => $event->appointment->id,
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
