<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\Services\BookingSlotHoldService;
use App\Http\Middleware\FraudCheckMiddleware;
use App\Http\Middleware\TenancyMiddleware;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;
use Carbon\CarbonImmutable;

final class ReleaseExpiredBookingSlotsJob implements ShouldBeUnique, ShouldQueue
{
    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 60;

    public array $backoff = [60, 300, 900];

    public function __construct(private readonly LoggerInterface $logger,
        public int $tenantId,
        private readonly LogManager $log,) {
        $this->onQueue('default');
    }

    public function tags(): array
    {
        return ['beauty', 'release-slots', 'tenant:'.$this->tenantId];
    }

    public function uniqueId(): string
    {
        return sprintf('release_expired_slots_%d_%s', $this->tenantId, CarbonImmutable::now()->format('YmdHi'));
    }

    public function handle(BookingSlotHoldService $slotHoldService): void
    {
        $correlationId = Str::uuid()->toString();

        $this->log->channel('audit')->$this->logger->info('beauty.job.release_expired.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $this->tenantId,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            $releasedCount = $slotHoldService->expireHeldSlots($this->tenantId);

            $this->log->channel('audit')->$this->logger->info('beauty.job.release_expired.success', [
                'correlation_id' => $correlationId,
                'tenant_id' => $this->tenantId,
                'released_count' => $releasedCount,
            ]);
        } catch (Throwable $e) {
            $this->log->channel('audit')->critical('beauty.job.release_expired.failed', [
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
        $this->log->channel('audit')->critical('beauty.job.release_expired.failed_permanent', [
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    public function middleware(): array
    {
        return [
            new TenancyMiddleware(),
            new FraudCheckMiddleware(),
        ];
    }
}
