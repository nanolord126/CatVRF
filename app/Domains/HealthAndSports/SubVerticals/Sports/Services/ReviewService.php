<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Carbon\CarbonImmutable;

use App\Services\Fraud\FraudControlService;

use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;

final readonly class ReviewService
{
    public function __construct(private readonly BusDispatcher $bus,
        private readonly FraudControlService $fraudControlService,
        private readonly DatabaseManager $db,
        private readonly Request $request,
        private readonly LoggerInterface $logger) {}


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
        $this->fraudControlService->check('create', ['context' => __CLASS__]);
        $correlationId = Str::uuid()->toString();
        $this->logger->$this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            if ($rating < 1 || $rating > 5) {
                throw new \InvalidArgumentException('Rating must be between 1 and 5');
            }

            $this->logger->$this->logger->info('Creating review', [
                'studio_id' => $studioId,
                'trainer_id' => $trainerId,
                'rating' => $rating,
                'correlation_id' => $correlationId,
            ]);

            $review = $this->db->transaction(function () use (
                $studioId,
                $trainerId,
                $reviewerId,
                $rating,
                $title,
                $content,
                $categories,
                $verifiedPurchase,
                $bookingId,
                $correlationId
            ) {
                $review = Review::create([
                    'tenant_id' => tenant()?->id,
                    'studio_id' => $studioId,
                    'trainer_id' => $trainerId,
                    'reviewer_id' => $reviewerId,
                    'booking_id' => $bookingId,
                    'rating' => $rating,
                    'title' => $title,
                    'content' => $content,
                    'categories' => $categories,
                    'verified_purchase' => $verifiedPurchase,
                    'published_at' => CarbonImmutable::now(),
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

                ReviewSubmitted::$this->bus->dispatch($review, $correlationId);

                return $review;
            });

            $this->logger->$this->logger->info('Review created successfully', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            $this->logger->error('Failed to create review', [
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
        $this->fraudControlService->check('update', ['context' => __CLASS__]);
        $correlationId = Str::uuid()->toString();
        $this->logger->$this->logger->info('Service method called in Sports', ['correlation_id' => $correlationId]);

        try {
            $correlationId = $correlationId ?? Str::uuid()->toString();

            if ($rating < 1 || $rating > 5) {
                throw new \InvalidArgumentException('Rating must be between 1 and 5');
            }

            $this->logger->$this->logger->info('Updating review', [
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

            $this->logger->$this->logger->info('Review updated', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        } catch (Throwable $e) {
            $this->logger->error('Failed to update review', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);
            throw $e;
        }
    }
}
