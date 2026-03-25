<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Review;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReviewService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function createReview(
        int $hotelId,
        int $rating,
        string $title,
        string $content,
        int $guestId,
        ?array $categories = null,
        string $correlationId = '',
    ): Review {


        try {
            $this->log->channel('audit')->info('Creating review', [
                'hotel_id' => $hotelId,
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

            $review = $this->db->transaction(function () use (
                $hotelId,
                $rating,
                $title,
                $content,
                $guestId,
                $categories,
                $correlationId,
            ) {
                return Review::create([
                    'tenant_id' => tenant('id'),
                    'hotel_id' => $hotelId,
                    'guest_id' => $guestId,
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'categories' => $categories,
                    'verified_booking' => true,
                    'published_at' => now(),
                    'correlation_id' => $correlationId,
                ]);
            });

            // Update hotel rating
            $this->recalculateHotelRating($hotelId, $correlationId);

            $this->log->channel('audit')->info('Review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Review creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function recalculateHotelRating(int $hotelId, string $correlationId = ''): float
    {


        try {
            $this->log->channel('audit')->info('Recalculating hotel rating', [
                'hotel_id' => $hotelId,
                'correlation_id' => $correlationId,
            ]);

            $avgRating = Review::where('hotel_id', $hotelId)
                ->where('published_at', '!=', null)
                ->avg('rating') ?? 0;

            Hotel::findOrFail($hotelId)->update([
                'rating' => round($avgRating, 2),
            ]);

            $this->log->channel('audit')->info('Hotel rating updated', [
                'hotel_id' => $hotelId,
                'new_rating' => $avgRating,
                'correlation_id' => $correlationId,
            ]);

            return $avgRating;
        } catch (Throwable $e) {
            $this->log->channel('audit')->error('Failed to recalculate rating', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
