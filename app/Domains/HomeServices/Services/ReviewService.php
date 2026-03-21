<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use App\Domains\HomeServices\Models\ServiceReview;
use App\Domains\HomeServices\Events\ReviewSubmitted;

final class ReviewService
{
    public function __construct() {}

    public function createReview(
        int $contractorId,
        int $reviewerId,
        int $rating,
        string $title,
        string $content,
        ?int $jobId = null,
        string $correlationId = ''
    ): ServiceReview {
        try {
            return \DB::transaction(function () use ($contractorId, $reviewerId, $rating, $title, $content, $jobId, $correlationId) {
                if ($rating < 1 || $rating > 5) {
                    throw new \InvalidArgumentException('Rating must be between 1 and 5');
                }

                $review = ServiceReview::create([
                    'tenant_id' => tenant('id'),
                    'contractor_id' => $contractorId,
                    'reviewer_id' => $reviewerId,
                    'job_id' => $jobId,
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'published_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                $contractor = \App\Domains\HomeServices\Models\Contractor::findOrFail($contractorId);
                $avgRating = $contractor->reviews()->whereNotNull('published_at')->avg('rating') ?? 0;
                $contractor->update([
                    'rating' => round($avgRating, 2),
                    'review_count' => $contractor->reviews()->whereNotNull('published_at')->count(),
                ]);

                ReviewSubmitted::dispatch($review, $correlationId);

                \Log::channel('audit')->info('Review submitted', [
                    'review_id' => $review->id,
                    'contractor_id' => $contractorId,
                    'rating' => $rating,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create review', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateReview(
        ServiceReview $review,
        int $rating,
        string $title,
        string $content,
        string $correlationId = ''
    ): ServiceReview {
        try {
            return \DB::transaction(function () use ($review, $rating, $title, $content, $correlationId) {
                $review->update([
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'correlation_id' => $correlationId,
                ]);

                $contractor = $review->contractor;
                $avgRating = $contractor->reviews()->whereNotNull('published_at')->avg('rating') ?? 0;
                $contractor->update(['rating' => round($avgRating, 2)]);

                return $review;
            });
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update review', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
