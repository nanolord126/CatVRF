<?php declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Review;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReviewService
{
    public function createReview(
        int $hotelId,
        int $rating,
        string $title,
        string $content,
        ?array $categories = null,
        string $correlationId = '',
    ): Review {
        try {
            Log::channel('audit')->info('Creating review', [
                'hotel_id' => $hotelId,
                'rating' => $rating,
                'correlation_id' => $correlationId,
            ]);

            if ($rating < 1 || $rating > 5) {
                throw new \Exception('Rating must be between 1 and 5');
            }

            $review = DB::transaction(function () use (
                $hotelId,
                $rating,
                $title,
                $content,
                $categories,
                $correlationId,
            ) {
                return Review::create([
                    'tenant_id' => tenant('id'),
                    'hotel_id' => $hotelId,
                    'guest_id' => auth()->id(),
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

            Log::channel('audit')->info('Review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Review creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function recalculateHotelRating(int $hotelId, string $correlationId = ''): float
    {
        try {
            Log::channel('audit')->info('Recalculating hotel rating', [
                'hotel_id' => $hotelId,
                'correlation_id' => $correlationId,
            ]);

            $avgRating = Review::where('hotel_id', $hotelId)
                ->where('published_at', '!=', null)
                ->avg('rating') ?? 0;

            Hotel::findOrFail($hotelId)->update([
                'rating' => round($avgRating, 2),
            ]);

            Log::channel('audit')->info('Hotel rating updated', [
                'hotel_id' => $hotelId,
                'new_rating' => $avgRating,
                'correlation_id' => $correlationId,
            ]);

            return $avgRating;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to recalculate rating', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
