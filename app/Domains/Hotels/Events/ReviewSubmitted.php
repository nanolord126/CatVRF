<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use App\Domains\Hotels\Models\Review;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ReviewSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Review $review,
        public readonly string $correlationId = '',
    ) {}
}
