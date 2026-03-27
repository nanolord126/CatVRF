<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Listeners;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Events\MusicStockChanged;
use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Jobs\StockThresholdJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for MusicStockChanged event.
 */
final class CheckMusicStockThreshold implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(MusicStockChanged $event): void
    {
        Log::channel('audit')->info('Processing music stock change event', [
            'instrument_id' => $event->instrument->id,
            'old_stock' => $event->oldStock,
            'new_stock' => $event->newStock,
            'correlation_id' => $event->correlationId,
        ]);

        // Dispatch background job to check thresholds and notify
        StockThresholdJob::dispatch(
            $event->instrument->id,
            $event->correlationId
        );
    }
}
