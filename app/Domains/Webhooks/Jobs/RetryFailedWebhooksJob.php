<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Jobs;

use App\Domains\Webhooks\Models\WebhookDelivery;
use App\Domains\Webhooks\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RetryFailedWebhooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    public function onQueue(): string
    {
        return 'webhooks';
    }

    public function handle(): void
    {
        $deliveries = WebhookDelivery::where('failed_at', '!=', null)
            ->where('next_retry_at', '<=', now())
            ->with('webhook')
            ->get();

        foreach ($deliveries as $delivery) {
            if ($delivery->shouldRetry()) {
                try {
                    $this->webhookService->deliver(
                        $delivery->webhook,
                        $delivery->event_type,
                        $delivery->payload,
                        $delivery->correlation_id ?? ''
                    );

                    $this->logger->info('Webhook retry succeeded', [
                        'delivery_id' => $delivery->id,
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Webhook retry failed', [
                        'delivery_id' => $delivery->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
