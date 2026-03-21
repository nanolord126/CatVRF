<?php declare(strict_types=1);

namespace App\Domains\Sports\Events;

use App\Domains\Sports\Models\Review;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Review $review,
        public string $correlationId = '',
    ) {}
}
