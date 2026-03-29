<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class InvalidateMasterAvailabilityCacheListener
{
    public function handle(object $event): void
    {
        if (!isset($event->masterId)) {
            return;
        }

        try {
            $cacheTag = "master_availability_{$event->masterId}";
            Cache::store('redis')->tags([$cacheTag])->flush();

            Log::channel('audit')->info('Master availability cache invalidated', [
                'master_id' => $event->masterId,
                'event' => class_basename($event),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to invalidate master availability cache', [
                'master_id' => $event->masterId ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
