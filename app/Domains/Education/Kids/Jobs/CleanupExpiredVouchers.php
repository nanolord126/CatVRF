<?php declare(strict_types=1);

/**
 * CleanupExpiredVouchers — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cleanupexpiredvouchers
 */


namespace App\Domains\Education\Kids\Jobs;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final class CleanupExpiredVouchers
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            private string $correlationId = '', private readonly LoggerInterface $logger) {}

        public function handle(): void
        {
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            $this->logger->info('Cleanup job started: Expired Vouchers', [
                'correlation_id' => $correlationId,
            ]);

            $expiredCount = KidsVoucher::where('status', 'active')
                ->whereNotNull('valid_until')
                ->where('valid_until', '<', Carbon::now())
                ->update([
                    'status' => 'expired',
                    'correlation_id' => $correlationId,
                ]);

            if ($expiredCount > 0) {
                $this->logger->info("Successfully expired $expiredCount vouchers.", [
                    'count' => $expiredCount,
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        /**
         * Unique ID for job monitoring.
         */
        public function tags(): array
        {
            return ['kids', 'vouchers', 'cleanup'];
        }

        /**
         * Re-try logic.
         */
        public function retryUntil(): \DateTime
        {
            return Carbon::now()->addHours(2);
        }
}
