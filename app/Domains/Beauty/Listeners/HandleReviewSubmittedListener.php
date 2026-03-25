declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\ReviewSubmitted;
use App\Domains\Beauty\Jobs\UpdateMasterRatingsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final /**
 * HandleReviewSubmittedListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HandleReviewSubmittedListener implements ShouldQueue
{
    public function handle(ReviewSubmitted $event): void
    {
        $review = $event->review;

        // Trigger master rating update
        if ($review->master_id) {
            UpdateMasterRatingsJob::dispatch($event->correlationId);
            $this->cache->forget("master_reviews:{$review->master_id}");
        }

        // Trigger salon rating update
        if ($review->salon_id) {
            $this->cache->forget("salon_reviews:{$review->salon_id}");
        }

        $this->log->channel('audit')->info('ReviewSubmitted event handled', [
            'review_id' => $review->id,
            'master_id' => $review->master_id,
            'salon_id' => $review->salon_id,
            'rating' => $review->rating,
            'correlation_id' => $event->correlationId,
        ]);
    }
}
