<?php declare(strict_types=1);

namespace App\Domains\Tickets\Events;

use App\Domains\Tickets\Models\EventReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class EventReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EventReview $review,
        public string $correlationId = '',
    ) {}
}
