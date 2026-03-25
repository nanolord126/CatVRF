<?php declare(strict_types=1);

namespace App\Domains\Pet\Listeners;

use App\Domains\Pet\Events\BoardingReservationCreated;
use App\Models\BalanceTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DeductBoardingCommissionListener implements ShouldQueue
{
    public function handle(BoardingReservationCreated $event): void
    {
        try {
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

                $this->log->channel('audit')->info('Pet boarding commission deducted', [
                    'reservation_id' => $event->reservation->id,
                    'clinic_id' => $clinic->id,
                    'amount' => $commissionAmount / 100,
                    'correlation_id' => $event->correlationId,
                    'wallet_id' => $wallet->id,
                ]);
            });
        } catch (\Throwable $e) {
            $this->log->error('Failed to deduct boarding commission', [
                'reservation_id' => $event->reservation->id,
                'correlation_id' => $event->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
