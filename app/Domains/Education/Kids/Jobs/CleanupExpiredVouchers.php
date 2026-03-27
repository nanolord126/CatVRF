<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Jobs;

use App\Domains\Education\Kids\Models\KidsVoucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CleanupExpiredVouchers - Auto-expires vouchers past their valid_until date.
 * Frequency: Once a day.
 * Layer: Jobs (8/9)
 */
final class CleanupExpiredVouchers implements ShouldQueue
{
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
