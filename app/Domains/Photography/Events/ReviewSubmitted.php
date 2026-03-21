<?php

declare(strict_types=1);

namespace App\Domains\Photography\Events;

use App\Domains\Photography\Models\PhotoReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewSubmitted
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public function __construct(
		public readonly PhotoReview $review,
		public readonly string $correlationId
	) {}
}
