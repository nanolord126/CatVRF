<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionSocialMediaTrendService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class CollectSocialMediaTrendsJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('social-media');
        $this->delay(now()->addMinutes(30));
    }

    public function handle(FashionSocialMediaTrendService $service): void
    {
        try {
            $service->collectTrendData($this->correlationId);
            
            Log::channel('audit')->info('Social media trends collected', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to collect social media trends', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
