<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Tickets\Models\{EventReview, Event};
use App\Domains\Tickets\Events\EventReviewSubmitted;
use Illuminate\Support\Facades\DB;
use Throwable;

final class EventReviewService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createReview(
        int $eventId,
        int $buyerId,
        int $rating,
        string $title,
        string $content,
        string $correlationId = '',
    ): EventReview {


        try {
            Log::channel('audit')->info('Creating event review', [
                'event_id' => $eventId,
                'buyer_id' => $buyerId,
                'rating' => $rating,
                'correlation_id' => $correlationId,
            ]);

            if ($rating < 1 || $rating > 5) {
                throw new \Exception('Rating must be between 1 and 5');
            }

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );

            $review = DB::transaction(function () use ($eventId, $buyerId, $rating, $title, $content, $correlationId) {
                $review = EventReview::create([
                    'tenant_id' => tenant('id'),
                    'event_id' => $eventId,
                    'buyer_id' => $buyerId,
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'verified_purchase' => true,
                    'published_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Recalculate event rating
                $avgRating = EventReview::where('event_id', $eventId)->avg('rating');
                $event = Event::findOrFail($eventId);
                $event->update([
                    'rating' => round($avgRating, 1),
                    'review_count' => $event->reviews()->count() + 1,
                ]);

                EventReviewSubmitted::dispatch($review, $correlationId);

                return $review;
            });

            Log::channel('audit')->info('Event review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create event review', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
