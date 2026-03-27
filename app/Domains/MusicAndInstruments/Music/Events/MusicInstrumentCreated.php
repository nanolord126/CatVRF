<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Events;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicInstrument;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new musical instrument is registered.
 */
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
