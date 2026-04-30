<?php

declare(strict_types=1);

namespace Modules\Recommendation\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Recommendation\Domain\Events\ImpressionTracked;
use Modules\Recommendation\Application\Jobs\UpdateUserFeaturesJob;

final class ImpressionTrackedListener
{
    public function handle(ImpressionTracked $event): void
    {
        try {
            Log::info('Impression tracked', [
                'tenant_id' => $event->tenantId,
                'user_id' => $event->userId,
                'item_id' => $event->itemId,
                'position' => $event->position,
                'scenario' => $event->scenario,
                'source' => $event->source,
            ]);

            if ($event->position === 0 && $event->scenario === 'home_feed') {
                UpdateUserFeaturesJob::dispatch($event->tenantId, $event->userId);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to handle impression tracked event', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
