<?php declare(strict_types=1);

namespace App\Domains\Fashion\Jobs;

use App\Domains\Fashion\Services\FashionEmailCampaignService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class SendScheduledEmailCampaignJob implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        private readonly int $campaignId,
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {
        $this->onQueue('email');
    }

    public function handle(FashionEmailCampaignService $service): void
    {
        try {
            $service->sendCampaign($this->campaignId, $this->correlationId);
            
            Log::channel('audit')->info('Scheduled email campaign sent', [
                'campaign_id' => $this->campaignId,
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to send scheduled email campaign', [
                'campaign_id' => $this->campaignId,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
