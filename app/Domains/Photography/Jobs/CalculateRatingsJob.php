declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Jobs;

use App\Domains\Photography\Models\PhotoStudio;
use App\Domains\Photography\Models\Photographer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CalculateRatingsJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CalculateRatingsJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 1;
	public int $timeout = 120;

	public function handle(): void
	{
		try {
			$this->db->transaction(function () {
				$studios = PhotoStudio::all();
				foreach ($studios as $studio) {
					$avgRating = $studio->reviews()->avg('rating') ?? 0;
					$reviewCount = $studio->reviews()->count();

					$studio->update([
						'rating' => $avgRating,
						'review_count' => $reviewCount,
					]);
				}

				$photographers = Photographer::all();
				foreach ($photographers as $photographer) {
					$avgRating = $photographer->reviews()->avg('rating') ?? 0;
					$photographer->update(['rating' => $avgRating]);
				}

				$this->log->channel('audit')->info('Photography: Batch ratings calculated', [
					'studios_count' => $studios->count(),
					'photographers_count' => $photographers->count(),
				]);
			});
		} catch (\Exception $e) {
			$this->log->channel('audit')->error('Photography: Ratings calculation failed', [
				'error' => $e->getMessage(),
			]);
			throw $e;
		}
	}
}
