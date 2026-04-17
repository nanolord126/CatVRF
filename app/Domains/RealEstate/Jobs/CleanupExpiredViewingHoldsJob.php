<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Jobs;

use App\Domains\RealEstate\Models\PropertyViewing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

final class CleanupExpiredViewingHoldsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly ?string $correlationId = null
    ) {
        $this->onQueue('real-estate-holds');
    }

    public function handle(): void
    {
        $correlationId = $this->correlationId ?? \Illuminate\Support\Str::uuid()->toString();

        try {
            $expiredViewings = PropertyViewing::expired()
                ->where('status', 'held')
                ->where('hold_expires_at', '<=', now())
                ->get();

            $cleanedCount = 0;

            foreach ($expiredViewings as $viewing) {
                $this->releaseExpiredHold($viewing, $correlationId);
                $cleanedCount++;
            }

            Log::channel('audit')->info('Expired viewing holds cleaned up', [
                'cleaned_count' => $cleanedCount,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed to cleanup expired viewing holds', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            $this->release(60);
        }
    }

    private function releaseExpiredHold(PropertyViewing $viewing, string $correlationId): void
    {
        $slotKey = "viewing_slot:{$viewing->property_id}:{$viewing->scheduled_at->format('Y-m-d-H-i')}";
        $holdKey = "viewing_hold:{$viewing->user_id}:{$viewing->property_id}";

        Redis::del($slotKey);
        Redis::del($holdKey);

        $viewing->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'hold_expired',
        ]);

        Log::channel('audit')->info('Viewing hold expired and released', [
            'viewing_id' => $viewing->id,
            'property_id' => $viewing->property_id,
            'user_id' => $viewing->user_id,
            'scheduled_at' => $viewing->scheduled_at,
            'hold_expires_at' => $viewing->hold_expires_at,
            'correlation_id' => $correlationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('audit')->error('CleanupExpiredViewingHoldsJob failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'correlation_id' => $this->correlationId,
        ]);
    }
}
