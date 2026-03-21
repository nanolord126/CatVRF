<?php

declare(strict_types=1);

namespace App\Domains\Photography\Listeners;

use App\Domains\Photography\Events\ReviewSubmitted;
use App\Domains\Photography\Models\PhotoStudio;
use App\Domains\Photography\Models\Photographer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateRatingsListener implements ShouldQueue
{
	use InteractsWithQueue;

	public function handle(ReviewSubmitted $event): void
	{
		try {
			DB::transaction(function () use ($event) {
				$studio = PhotoStudio::find($event->review->photo_studio_id);
				$photographer = Photographer::find($event->review->photographer_id);

				if ($studio) {
					$studio->update([
						'rating' => $studio->reviews()->avg('rating') ?? 0,
						'review_count' => $studio->reviews()->count(),
					]);
				}

				if ($photographer) {
					$photographer->update([
						'rating' => $photographer->reviews()->avg('rating') ?? 0,
					]);
				}

				Log::channel('audit')->info('Photography: Ratings updated', [
					'studio_id' => $studio?->id,
					'photographer_id' => $photographer?->id,
					'correlation_id' => $event->correlationId,
				]);
			});
		} catch (\Exception $e) {
			Log::channel('audit')->error('Photography: Rating update failed', [
				'review_id' => $event->review->id,
				'error' => $e->getMessage(),
				'correlation_id' => $event->correlationId,
			]);
		}
	}
}
