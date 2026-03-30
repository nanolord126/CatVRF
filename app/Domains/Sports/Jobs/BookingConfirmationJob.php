<?php declare(strict_types=1);

namespace App\Domains\Sports\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingConfirmationJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private ?int $bookingId;
        private ?string $correlationId;

        public function __construct(int $bookingId = 0, string $correlationId = '')
        {
            $this->bookingId = $bookingId;
            $this->correlationId = $correlationId;
            $this->onQueue('bookings');

        }

        public function handle(BookingService $service): void
        {
            try {
                Log::channel('audit')->info('Starting booking confirmation', [
                    'booking_id' => $this->bookingId,
                    'correlation_id' => $this->correlationId,
                ]);

                $booking = Booking::findOrFail($this->bookingId);
                $service->confirmBooking($booking, $this->correlationId);

                Log::channel('audit')->info('Booking confirmed', [
                    'booking_id' => $this->bookingId,
                    'correlation_id' => $this->correlationId,
                ]);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Booking confirmation failed', [
                    'booking_id' => $this->bookingId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                $this->fail($e);
            }
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(1);
        }
}
