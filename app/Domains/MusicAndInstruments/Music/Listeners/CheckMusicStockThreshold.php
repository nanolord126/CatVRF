<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CheckMusicStockThreshold extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
