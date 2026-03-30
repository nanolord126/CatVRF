<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleanupExpiredVouchers extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        public function __construct(
            public readonly string $correlationId = '',
        ) {}

        public function handle(): void
        {
            $correlationId = $this->correlationId ?: (string) Str::uuid();

            Log::channel('audit')->info('Cleanup job started: Expired Vouchers', [
                'correlation_id' => $correlationId,
            ]);

            $expiredCount = KidsVoucher::where('status', 'active')
                ->whereNotNull('valid_until')
                ->where('valid_until', '<', now())
                ->update([
                    'status' => 'expired',
                    'correlation_id' => $correlationId,
                ]);

            if ($expiredCount > 0) {
                Log::channel('audit')->info("Successfully expired $expiredCount vouchers.", [
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
            return now()->addHours(2);
        }
}
