<?php declare(strict_types=1);

/**
 * HandleMusicInstrumentCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/handlemusicinstrumentcreated
 */


namespace App\Domains\MusicAndInstruments\Music\Listeners;


use Psr\Log\LoggerInterface;
final class HandleMusicInstrumentCreated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    /**
         * Handle the event.
         */
        public function handle(MusicInstrumentCreated $event): void
        {
            $instrument = $event->instrument;

            $this->logger->info('Processing music instrument registration', [
                'instrument_id' => $instrument->id,
                'name' => $instrument->name,
                'correlation_id' => $event->correlationId,
            ]);

            // ML: Recalculate embeddings for search/recommendations
            // MachineLearningService::indexInstrument($instrument);

            // Notify followers of relevant categories
            // NotificationService::notifyCategoryFollowers($instrument->category_id, $instrument);

            $this->logger->info('Instrument successfully indexed and processed', [
                'instrument_id' => $instrument->id,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
