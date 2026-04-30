<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Events\RecommendationServed;

final class RecommendationServedListener
{
    public function handle(RecommendationServed $event): void
    {
        try {
            Log::info('Recommendation served', [
                'tenant_id' => $event->tenantId,
                'user_id' => $event->userId,
                'scenario' => $event->scenario,
                'item_count' => $event->itemCount,
                'source' => $event->source,
                'model_version' => $event->modelVersion,
                'latency_ms' => $event->latencyMs,
                'correlation_id' => $event->correlationId,
            ]);

            if ($event->latencyMs > 500) {
                Log::warning('High recommendation latency', [
                    'latency_ms' => $event->latencyMs,
                    'scenario' => $event->scenario,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to handle recommendation served event', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
