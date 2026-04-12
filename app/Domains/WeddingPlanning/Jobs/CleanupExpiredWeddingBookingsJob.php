<?php declare(strict_types=1);

namespace App\Domains\WeddingPlanning\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


use Psr\Log\LoggerInterface;
final class CleanupExpiredWeddingBookingsJob
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        private readonly string $correlationId;

        public function __construct(?string $correlationId = null, private readonly LoggerInterface $logger)
        {
            $this->correlationId = $correlationId ?? (string) Str::uuid();
        }

        public function handle(): void
        {
            $this->logger->info('Cleaning up expired wedding bookings [Job Start]', [
                'correlation_id' => $this->correlationId,
            ]);

            try {
                // Резерв на 20 минут согласно канону 2026 (Wedding rule 1.3)
                $expirationLimit = Carbon::now()->subMinutes(20);

                $expiredBookingsCount = WeddingBooking::query()
                    ->where('status', 'pending')
                    ->where('reserved_at', '<', $expirationLimit)
                    ->where('payment_status', 'not_paid')
                    ->whereNull('paid_at')
                    ->count();

                if ($expiredBookingsCount > 0) {
                    WeddingBooking::query()
                        ->where('status', 'pending')
                        ->where('reserved_at', '<', $expirationLimit)
                        ->where('payment_status', 'not_paid')
                        ->update([
                            'status' => 'cancelled',
                            'tags' => ['expired_auto'],
                            'correlation_id' => $this->correlationId,
                        ]);

                    $this->logger->info("Expired wedding bookings cleaned up", [
                        'count' => $expiredBookingsCount,
                        'correlation_id' => $this->correlationId,
                    ]);
                } else {
                    $this->logger->info("No expired wedding bookings found", [
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error("CleanupExpiredWeddingBookingsJob Error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        public function tags(): array
        {
            return ['wedding', 'cleanup', 'expiration', $this->correlationId];
        }
}
