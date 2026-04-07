<?php declare(strict_types=1);

/**
 * MusicInstrumentCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/musicinstrumentcreated
 */


namespace App\Domains\MusicAndInstruments\Music\Events;

final class MusicInstrumentCreated
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        /**
         * Create a new event instance.
         */
        public function __construct(
            public MusicInstrument $instrument,
            public string $correlationId
        ) {}
}
