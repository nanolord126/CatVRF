<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CheckBookingPaymentTimeout extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private string $bookingUuid,
            private string $correlationId
        ) {}

        /**
         * Выполнение джобы
         */
        public function handle(): void
        {
            try {
                $booking = VIPBooking::where('uuid', $this->bookingUuid)->first();

                if (!$booking || $booking->payment_status === 'paid' || $booking->status === 'cancelled') {
                    return;
                }

                // Отмена бронирования, если депозит не был оплачен
                \Illuminate\Support\Facades\DB::transaction(function () use ($booking) {
                    $booking->update([
                        'status' => 'cancelled',
                        'notes' => ($booking->notes ?? '') . ' [Auto-cancelled due to payment timeout]',
                        'correlation_id' => $this->correlationId,
                    ]);

                    // Возврат стока (если это товар)
                    $bookable = $booking->bookable;
                    if ($bookable instanceof \App\Domains\Luxury\Models\LuxuryProduct) {
                        $bookable->decrement('hold_stock');
                    }

                    Log::channel('audit')->info('VIP Booking Expired and Cancelled', [
                        'booking_uuid' => $booking->uuid,
                        'correlation_id' => $this->correlationId,
                    ]);
                });

            } catch (Throwable $e) {
                Log::channel('audit')->error('VIP Booking Payment Timeout Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e; // Для retry
            }
        }
}
