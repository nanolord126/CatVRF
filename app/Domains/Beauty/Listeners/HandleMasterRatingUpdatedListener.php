<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\MasterRatingUpdated;
use App\Domains\Beauty\Jobs\RecalculateSalonRatingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class HandleMasterRatingUpdatedListener implements ShouldQueue
{
    public function handle(MasterRatingUpdated $event): void
    {
        $master = $event->master;

        // Invalidate master rating cache
        Cache::forget("master_rating:{$master->id}");

        // Trigger salon rating recalculation
        if ($master->salon_id) {
            RecalculateSalonRatingJob::dispatch($event->correlationId);
        }

        Log::channel('audit')->info('MasterRatingUpdated event handled', [
            'master_id' => $master->id,
            'old_rating' => $event->oldRating,
            'new_rating' => $event->newRating,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
