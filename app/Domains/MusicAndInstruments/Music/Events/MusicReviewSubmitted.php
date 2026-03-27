<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Events;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new music review is submitted.
 */
final class MusicReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public MusicReview $review,
        public string $correlationId
    ) {}
}
