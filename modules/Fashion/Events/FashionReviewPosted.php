<?php declare(strict_types=1);

namespace Modules\Fashion\Events;

use App\Domains\Fashion\Models\FashionReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FashionReviewPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FashionReview $review,
        public readonly string $correlationId
    ) {}
}
