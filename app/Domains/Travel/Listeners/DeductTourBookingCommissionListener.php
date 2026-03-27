<?php declare(strict_types=1);

namespace App\Domains\Travel\Listeners;

use App\Domains\Travel\Events\TourBooked;
use App\Models\BalanceTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DeductTourBookingCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        public string $queue = 'travel',
    ) {}

    public function handle(TourBooked $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                $wallet = $event->booking->agency->owner->wallet;

                if ($wallet === null) {
                    throw new \RuntimeException('Agency owner wallet not found');
                }

                $wallet->lockForUpdate();

                $commissionInCents = (int)($event->booking->commission_amount * 100);

                $wallet->decrement('current_balance', $commissionInCents);

                BalanceTransaction::create([
                    'tenant_id' => $event->booking->tenant_id,
                    'wallet_id' => $wallet->id,
                    'type' => 'commission',
                    'amount' => $commissionInCents,
                    'description' => "Commission for tour booking #{$event->booking->booking_number}",
                    'reference_type' => 'travel_booking',
                    'reference_id' => $event->booking->id,
                    'correlation_id' => $event->correlationId,
                ]);

                Log::channel('audit')->info('Travel commission deducted', [
                    'booking_id' => $event->booking->id,
                    'booking_number' => $event->booking->booking_number,
                    'agency_id' => $event->booking->agency_id,
                    'commission_amount' => $event->booking->commission_amount,
                    'correlation_id' => $event->correlationId,
                    'wallet_id' => $wallet->id,
                    'timestamp' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Travel commission deduction failed', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
