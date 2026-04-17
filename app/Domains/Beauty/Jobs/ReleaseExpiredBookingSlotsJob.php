<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use App\Domains\Beauty\Services\BookingSlotHoldService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReleaseExpiredBookingSlotsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $uniqueFor = 60;

    public function __construct(
        public int $tenantId,
    ) {
        $this->onQueue('beauty');
    }

    public function uniqueId(): string
    {
        return sprintf('release_expired_slots_%d_%s', $this->tenantId, now()->format('YmdHi'));
    }

    public function handle(BookingSlotHoldService $slotHoldService): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        Log::channel('audit')->info('beauty.job.release_expired.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $this->tenantId,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            $releasedCount = $slotHoldService->expireHeldSlots($this->tenantId);

            Log::channel('audit')->info('beauty.job.release_expired.success', [
                'correlation_id' => $correlationId,
                'tenant_id' => $this->tenantId,
                'released_count' => $releasedCount,
            ]);
        } catch (Throwable $e) {
            Log::channel('audit')->critical('beauty.job.release_expired.failed', [
                'correlation_id' => $correlationId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->release(30);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('audit')->critical('beauty.job.release_expired.failed_permanent', [
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function middleware(): array
    {
        return [
            new \App\Http\Middleware\TenancyMiddleware(),
            new \App\Http\Middleware\FraudCheckMiddleware(),
        ];
    }
}
