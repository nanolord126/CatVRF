<?php declare(strict_types=1);

namespace App\Domains\Pet\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductBoardingCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(BoardingReservationCreated $event): void
        {
            try {
                DB::transaction(function () use ($event) {
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

                    Log::channel('audit')->info('Pet boarding commission deducted', [
                        'reservation_id' => $event->reservation->id,
                        'clinic_id' => $clinic->id,
                        'amount' => $commissionAmount / 100,
                        'correlation_id' => $event->correlationId,
                        'wallet_id' => $wallet->id,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to deduct boarding commission', [
                    'reservation_id' => $event->reservation->id,
                    'correlation_id' => $event->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
