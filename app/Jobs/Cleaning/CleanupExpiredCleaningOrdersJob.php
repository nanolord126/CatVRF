<?php declare(strict_types=1);

namespace App\Jobs\Cleaning;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class CleanupExpiredCleaningOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * Parameters for the job (correlation tracking).
         */
        private string $correlationId;
        private int $expirationMinutes;

        /**
         * Create a new job instance.
         */
        public function __construct(
            ?string $correlationId = null,
            int $expirationMinutes = 20,
    ) {
            $this->correlationId = $correlationId ?? Str::uuid()->toString();
            $this->expirationMinutes = $expirationMinutes;
        }

        /**
         * Execute the job logic.
         * Identifying expired 'pending' orders and cleaning up resources.
         */
        public function handle(CleaningBookingService $bookingService, LogManager $logger): void
        {
            try {
                // Find orders stuck in pending status longer than expiration buffer
                $expiredOrders = CleaningOrder::where('status', 'pending')
                    ->where('created_at', '<', now()->subMinutes($this->expirationMinutes))
                    ->get();

                if ($expiredOrders->isEmpty()) {
                    $logger->channel('audit')->info('[CleaningJob] No expired orders found.', [
                        'correlation_id' => $this->correlationId,
                    ]);
                    return;
                }

                foreach ($expiredOrders as $order) {
                    // Perform the cleanup through the secure service layer
                    $bookingService->cancelOrder($order->uuid, 'Automated expiration: No deposit paid within window.', $this->correlationId);

                    $logger->channel('audit')->warning('[CleaningJob] Cancelled expired order.', [
                        'order_uuid' => $order->uuid,
                        'correlation_id' => $this->correlationId,
                        'client_id' => $order->user_id,
                    ]);
                }

                $logger->channel('audit')->info('[CleaningJob] Expired Orders Cleanup Completed.', [
                    'count' => $expiredOrders->count(),
                    'correlation_id' => $this->correlationId,
                ]);

            } catch (Throwable $e) {
                $logger->channel('audit')->critical('[CleaningJob] Cleanup Failed Critical Error!', [
                    'correlation_id' => $this->correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Re-throw to trigger queue retry if needed
                throw $e;
            }
        }

        /**
         * Define tags for Horizon/Telescope profiling.
         */
        public function tags(): array
        {
            return ['cleaning', 'maintenance', 'cleanup', $this->correlationId];
        }
}
