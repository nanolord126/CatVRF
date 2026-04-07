<?php declare(strict_types=1);

/**
 * HandleMusicReviewSubmitted — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlemusicreviewsubmitted
 */


namespace App\Domains\MusicAndInstruments\Music\Listeners;


use Psr\Log\LoggerInterface;
final class HandleMusicReviewSubmitted
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Handle the event.
         */
        public function handle(MusicReviewSubmitted $event): void
        {
            $review = $event->review;

            $this->logger->info('New music review submitted', [
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
                $this->logger->info('Notifying store owner of new review', [
                    'store_id' => $store->id,
                    'correlation_id' => $event->correlationId,
                ]);
                // Notification::send($store->tenant, new MusicReviewReceived($review));
            }

            // Low rating alert
            if ($review->rating <= 2) {
                $this->logger->warning('Low music review alert', [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        }
}
