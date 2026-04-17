<?php declare(strict_types=1);

namespace App\Domains\Security\Jobs;

use App\Domains\Security\Models\RateLimitRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class CleanupExpiredRateLimitRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function onQueue(): string
    {
        return 'audit-logs';
    }

    public function handle(): void
    {
        $deleted = RateLimitRecord::where('blocked_until', '<', now())
            ->delete();

        Log::channel('security')->info('Expired rate limit records cleaned up', [
            'deleted_count' => $deleted,
        ]);
    }
}
