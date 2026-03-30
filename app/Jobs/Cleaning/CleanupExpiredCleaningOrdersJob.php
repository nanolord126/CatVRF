<?php declare(strict_types=1);

namespace App\Jobs\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupExpiredCleaningOrdersJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        /**
         * Parameters for the job (correlation tracking).
         */
        public readonly string $correlationId;
        public readonly int $expirationMinutes;

        /**
         * Create a new job instance.
         */
        public function __construct(
            string $correlationId = null,
            int $expirationMinutes = 20 // Default 20 mins reserve rule
        ) {
            $this->correlationId = $correlationId ?? Str::uuid()->toString();
            $this->expirationMinutes = $expirationMinutes;

            // Audit startup
            Log::channel('audit')->info('[CleaningJob] Expired Orders Cleanup Started', [
                'correlation_id' => $this->correlationId,
                'expiration_mins' => $this->expirationMinutes,
            ]);
        }

        /**
         * Execute the job logic.
         * Identifying expired 'pending' orders and cleaning up resources.
         */
        public function handle(CleaningBookingService $bookingService): void
        {
            try {
                // Find orders stuck in pending status longer than expiration buffer
                $expiredOrders = CleaningOrder::where('status', 'pending')
                    ->where('created_at', '<', now()->subMinutes($this->expirationMinutes))
                    ->get();

                if ($expiredOrders->isEmpty()) {
                    Log::channel('audit')->info('[CleaningJob] No expired orders found.', [
                        'correlation_id' => $this->correlationId,
                    ]);
                    return;
                }

                foreach ($expiredOrders as $order) {
                    // Perform the cleanup through the secure service layer
                    $bookingService->cancelOrder($order->uuid, 'Automated expiration: No deposit paid within window.', $this->correlationId);

                    Log::channel('audit')->warning('[CleaningJob] Cancelled expired order.', [
                        'order_uuid' => $order->uuid,
                        'correlation_id' => $this->correlationId,
                        'client_id' => $order->user_id,
                    ]);
                }

                Log::channel('audit')->info('[CleaningJob] Expired Orders Cleanup Completed.', [
                    'count' => $expiredOrders->count(),
                    'correlation_id' => $this->correlationId,
                ]);

            } catch (Throwable $e) {
                Log::channel('audit')->critical('[CleaningJob] Cleanup Failed Critical Error!', [
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
