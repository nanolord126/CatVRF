<?php declare(strict_types=1);

namespace App\Domains\Sports\Jobs;

use App\Domains\Sports\Models\Booking;
use App\Domains\Sports\Services\BookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class BookingConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $bookingId;
    private string $correlationId;

    public function __construct(int $bookingId, string $correlationId = '')
    {
        $this->bookingId = $bookingId;
        $this->correlationId = $correlationId;
        $this->onQueue('bookings');
        $this->tags(['sports', 'bookings']);
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
