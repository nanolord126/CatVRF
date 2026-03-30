<?php declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HandleReviewSubmittedListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function handle(ReviewSubmitted $event): void
        {
            $review = $event->review;

            // Trigger master rating update
            if ($review->master_id) {
                UpdateMasterRatingsJob::dispatch($event->correlationId);
                Cache::forget("master_reviews:{$review->master_id}");
            }

            // Trigger salon rating update
            if ($review->salon_id) {
                Cache::forget("salon_reviews:{$review->salon_id}");
            }

            Log::channel('audit')->info('ReviewSubmitted event handled', [
                'review_id' => $review->id,
                'master_id' => $review->master_id,
                'salon_id' => $review->salon_id,
                'rating' => $review->rating,
                'correlation_id' => $event->correlationId,
            ]);
        }
}
