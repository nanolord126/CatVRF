<?php

declare(strict_types=1);


namespace App\Domains\Photography\Events;

use App\Domains\Photography\Models\PhotoReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ReviewSubmitted
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ReviewSubmitted
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public function __construct(
		public readonly PhotoReview $review,
		public readonly string $correlationId
	) {}
}
