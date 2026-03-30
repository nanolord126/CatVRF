<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleMusicReviewSubmitted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Handle the event.
         */
        public function handle(MusicReviewSubmitted $event): void
        {
            $review = $event->review;

            Log::channel('audit')->info('New music review submitted', [
                'review_id' => $review->id,
                'rating' => $review->rating,
                'user_id' => $review->user_id,
                'correlation_id' => $event->correlationId,
            ]);

            // Trigger ML re-scoring (mocked)
            // RecommendationService::handleFeedback($review);

            // Notify store owner
            $store = null;
            if ($review->instrument) $store = $review->instrument->store;
            elseif ($review->studio) $store = $review->studio->store;
            elseif ($review->lesson) $store = $review->lesson->store;

            if ($store) {
                Log::channel('audit')->info('Notifying store owner of new review', [
                    'store_id' => $store->id,
                    'correlation_id' => $event->correlationId,
                ]);
                // Notification::send($store->tenant, new MusicReviewReceived($review));
            }

            // Low rating alert
            if ($review->rating <= 2) {
                Log::channel('audit')->warning('Low music review alert', [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }
}
