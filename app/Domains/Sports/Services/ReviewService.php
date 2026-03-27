<?php declare(strict_types=1);

namespace App\Domains\Sports\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\Sports\Models\Review;
use App\Domains\Sports\Models\Studio;
use App\Domains\Sports\Models\Trainer;
use App\Domains\Sports\Events\ReviewSubmitted;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ReviewService
{
    public function createReview(
        ?int $studioId,
        ?int $trainerId,
        int $reviewerId,
        int $rating,
        string $title = '',
        string $content = '',
        array $categories = [],
        bool $verifiedPurchase = false,
        ?int $bookingId = null,
        ?string $correlationId = null,
    ): Review {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            if ($rating < 1 || $rating > 5) {
                throw new \Exception('Rating must be between 1 and 5');
            }

            Log::channel('audit')->info('Creating review', [
                'studio_id' => $studioId,
                'trainer_id' => $trainerId,
                'rating' => $rating,
                'correlation_id' => $correlationId,
            ]);

            $review = DB::transaction(function () use (
                $studioId,
                $trainerId,
                $reviewerId,
                $rating,
                $title,
                $content,
                $categories,
                $verifiedPurchase,
                $bookingId,
                $correlationId,
            ) {
                $review = Review::create([
                    'tenant_id' => tenant('id'),
                    'studio_id' => $studioId,
                    'trainer_id' => $trainerId,
                    'reviewer_id' => $reviewerId,
                    'booking_id' => $bookingId,
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'categories' => $categories,
                    'verified_purchase' => $verifiedPurchase,
                    'published_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                if ($studioId) {
                    $studio = Studio::findOrFail($studioId);
                    $avgRating = $studio->reviews()->avg('rating');
                    $studio->update([
                        'rating' => $avgRating,
                        'review_count' => $studio->reviews()->count(),
                    ]);
                }

                if ($trainerId) {
                    $trainer = Trainer::findOrFail($trainerId);
                    $avgRating = $trainer->reviews()->avg('rating');
                    $trainer->update([
                        'rating' => $avgRating,
                        'review_count' => $trainer->reviews()->count(),
                    ]);
                }

                ReviewSubmitted::dispatch($review, $correlationId);

                return $review;
            });

            Log::channel('audit')->info('Review created successfully', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to create review', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? null,
            ]);
            throw $e;
        }
    }

    public function updateReview(
        Review $review,
        int $rating,
        string $title = '',
        string $content = '',
        array $categories = [],
        ?string $correlationId = null,
    ): Review {
        $correlationId = Str::uuid()->toString();
        Log::channel('audit')->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            if ($rating < 1 || $rating > 5) {
                throw new \Exception('Rating must be between 1 and 5');
            }

            Log::channel('audit')->info('Updating review', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            $review->update([
                'rating' => $rating,
                'title' => $title,
                'content' => $content,
                'categories' => $categories,
                'correlation_id' => $correlationId,
            ]);

            if ($review->studio_id) {
                $studio = Studio::findOrFail($review->studio_id);
                $avgRating = $studio->reviews()->avg('rating');
                $studio->update([
                    'rating' => $avgRating,
                    'review_count' => $studio->reviews()->count(),
                ]);
            }

            Log::channel('audit')->info('Review updated', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to update review', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
