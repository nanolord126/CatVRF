<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleMusicInstrumentCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Handle the event.
         */
        public function handle(MusicInstrumentCreated $event): void
        {
            $instrument = $event->instrument;

            Log::channel('audit')->info('Processing music instrument registration', [
                'instrument_id' => $instrument->id,
                'name' => $instrument->name,
                'correlation_id' => $event->correlationId,
            ]);

            // ML: Recalculate embeddings for search/recommendations
            // MachineLearningService::indexInstrument($instrument);

            // Notify followers of relevant categories
            // NotificationService::notifyCategoryFollowers($instrument->category_id, $instrument);

            Log::channel('audit')->info('Instrument successfully indexed and processed', [
                'instrument_id' => $instrument->id,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
