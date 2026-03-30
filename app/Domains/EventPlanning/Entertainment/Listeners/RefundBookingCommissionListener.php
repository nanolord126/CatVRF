<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RefundBookingCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

        public function handle(BookingCreated $event): void
        {
            if ($event->booking->status !== 'cancelled') {
                return;
            }

            try {
                DB::transaction(function () use ($event) {
                    $commissionAmount = (int) ($event->booking->commission_amount * 100);
                    $wallet = \App\Models\Wallet::lockForUpdate()->where('tenant_id', $event->booking->tenant_id)->firstOrFail();
                    $wallet->increment('balance', $commissionAmount);

                    \App\Models\BalanceTransaction::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $commissionAmount,
                        'description' => "Booking commission refund #{$event->booking->id}",
                        'correlation_id' => $event->correlationId,
                    ]);

                    Log::channel('audit')->info('Booking commission refunded', [
                        'booking_id' => $event->booking->id,
                        'venue_id' => $event->booking->venue_id,
                        'customer_id' => $event->booking->customer_id,
                        'amount' => $event->booking->commission_amount,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to refund booking commission', [
                    'booking_id' => $event->booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
        }
}
