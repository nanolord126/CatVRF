<?php declare(strict_types=1);

/**
 * LogMusicInstrumentUpdated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/logmusicinstrumentupdated
 */


namespace App\Domains\MusicAndInstruments\Listeners;


use Psr\Log\LoggerInterface;
use App\Domains\MusicAndInstruments\Events\MusicInstrumentUpdated;
/**
 * Class LogMusicInstrumentUpdated
 *
 * Part of the MusicAndInstruments vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Event listener handling domain event side effects.
 * Runs asynchronously via queue when ShouldQueue is implemented.
 * All listeners maintain correlation_id chain.
 *
 * @package App\Domains\MusicAndInstruments\Listeners
 */
final class LogMusicInstrumentUpdated
{
    public function __construct(
        private readonly LoggerInterface $logger) {}

    /**
     * Handle the event.
     */
    public function handle(MusicInstrumentUpdated $event): void
    {
        $this->logger->info('MusicInstrument updated', [
            'model_id' => $event->musicInstrument->id,
            'correlation_id' => $event->correlationId,
            'tenant_id' => $event->musicInstrument->tenant_id ?? null,
        ]);
    }
}
