declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Listeners;

use App\Domains\Hotels\Events\BookingCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final /**
 * DeductBookingCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductBookingCommissionListener implements ShouldQueue
{
    public function handle(BookingCreated $event): void
    {
        try {
            $this->log->channel('audit')->info('Deducting booking commission', [
                'booking_id' => $event->booking->id,
                'correlation_id' => $event->correlationId,
                'amount' => $event->booking->commission_price,
            ]);

            $this->db->transaction(function () use ($event) {
                $hotel = $event->booking->hotel;
                
                // Deduct 14% commission from hotel balance
                $wallet = auth()->user()?->wallet ?? $hotel->wallet;
                
                if ($wallet) {
                    $wallet->balance -= $event->booking->commission_price;
                    $wallet->save();
                }

                $this->log->channel('audit')->info('Booking commission deducted', [
                    'booking_id' => $event->booking->id,
                    'correlation_id' => $event->correlationId,
                ]);
            });
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to deduct booking commission', [
                'booking_id' => $event->booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
