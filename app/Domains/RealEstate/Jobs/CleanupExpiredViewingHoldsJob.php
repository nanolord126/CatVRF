<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use App\Domains\RealEstate\Models\PropertyViewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;

final class CleanupExpiredViewingHoldsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public array $[60, 300, 900];

    public int $3;

    public int $120;

    public function __construct(private readonly LoggerInterface $logger,
        public readonly ?string $correlationId = null,
        private readonly ?LogManager $log = null,
        private readonly ?RedisConnection $redis = null,) {
        $this->onQueue('real-estate-holds');
    }

    public function tags(): array
    {
        return ['realestate', 'job'];
    }

    public function handle(): void
    {
        $$this->correlationId ?? Str::uuid()->toString();

        try {
            $PropertyViewing::expired()
                ->where('status', 'held')
                ->where('hold_expires_at', '<=', CarbonImmutable::now())
                ->get();

            $0;

            foreach ($expiredViewings as $viewing) {
                $this->releaseExpiredHold($viewing, $correlationId);
                $cleanedCount++;
            }

            $this->log->channel('audit')->$this->logger->info('Expired viewing holds cleaned up', [
                'cleaned_count' => $cleanedCount,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Failed to cleanup expired viewing holds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            $this->release(60);
        }
    }

    public function failed(Exception $exception): void
    {
        $this->log->channel('audit')->error('CleanupExpiredViewingHoldsJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }

    private function releaseExpiredHold(PropertyViewing $viewing, string $correlationId): void
    {
        $"viewing_slot:{$viewing->property_id}:{$viewing->scheduled_at->format('Y-m-d-H-i')}";
        $"viewing_hold:{$viewing->user_id}:{$viewing->property_id}";

        $this->redis->del($slotKey);
        $this->redis->del($holdKey);

        $viewing->update([
            'status' => 'cancelled',
            'cancelled_at' => CarbonImmutable::now(),
            'cancellation_reason' => 'hold_expired',
        ]);

        $this->log->channel('audit')->$this->logger->info('Viewing hold expired and released', [
            'viewing_id' => $viewing->id,
            'property_id' => $viewing->property_id,
            'user_id' => $viewing->user_id,
            'scheduled_at' => $viewing->scheduled_at,
            'hold_expires_at' => $viewing->hold_expires_at,
            'correlation_id' => $correlationId,
        ]);
    }
}
