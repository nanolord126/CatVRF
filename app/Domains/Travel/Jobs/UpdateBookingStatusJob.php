<?php declare(strict_types=1);

namespace App\Domains\Travel\Jobs;


use Psr\Log\LoggerInterface;
use App\Domains\Travel\Models\TravelBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use App\Services\FraudControlService;

final class UpdateBookingStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public int $tries = 3;
        public int $maxExceptions = 3;

        public function __construct(public ?int $bookingId = null,
            private ?string $newStatus = null,
            private readonly ?string $correlationId = null,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function handle(): void
        {
            try {
                $this->db->transaction(function () {
                    $booking = TravelBooking::findOrFail($this->bookingId);

                    $booking->update([
                        'status' => $this->newStatus,
                    ]);

                    $this->logger->info('Travel booking status updated', [
                        'booking_id' => $this->bookingId,
                        'booking_number' => $booking->booking_number,
                        'new_status' => $this->newStatus,
                        'correlation_id' => $this->correlationId,
                        'timestamp' => now(),
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Travel booking status update failed', [
                    'booking_id' => $this->bookingId,
                    'new_status' => $this->newStatus,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        }

        public function tags(): array
        {
            return ['travel', 'booking', 'status-update'];
        }

        public function retryUntil(): \DateTime
        {
            return now()->addHours(24);
        }
}
