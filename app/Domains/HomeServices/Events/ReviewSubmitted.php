<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Events;

use App\Domains\HomeServices\Models\ServiceReview;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\SerializesModels;

final class ReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceReview $review, public string $correlationId) {}
}
