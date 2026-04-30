<?php

declare(strict_types=1);

namespace App\Domains\Security\Jobs;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Illuminate\Support\Str;

use App\Domains\Security\Models\RateLimitRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;

final class CleanupExpiredRateLimitRecordsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        public readonly string $correlationId = '') {}

    public function onQueue(): string
    {
        return 'audit-logs';
    }

    public function handle(LogManager $log): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $deleted = RateLimitRecord::where('blocked_until', '<', CarbonImmutable::now())
            ->delete();

        $log->channel('security')->$this->logger->info('Expired rate limit records cleaned up', [
            'deleted_count' => $deleted,
        ]);
    }
}
