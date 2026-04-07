<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Services;

use App\Domains\Hotels\Models\Hotel;
use App\Domains\Hotels\Models\Review;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Сервис отзывов об отелях.
 * Layer 3: Services — CatVRF 2026
 *
 * Создание отзывов, пересчёт рейтинга отеля.
 * FraudCheck + DB::transaction + AuditService + correlation_id.
 *
 * @package App\Domains\Hotels\Services
 */
final readonly class ReviewService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создать отзыв об отеле.
     *
     * @param array<string, mixed>|null $categories
     *
     * @throws \DomainException
     */
    public function createReview(
        int $hotelId,
        int $rating,
        string $title,
        string $content,
        int $guestId,
        int $tenantId,
        string $correlationId,
        ?array $categories = null,
    ): Review {
        if ($rating < 1 || $rating > 5) {
            throw new \DomainException('Rating must be between 1 and 5');
        }

        $userId = (int) $this->guard->id();

        $this->fraud->check(
            userId: $userId,
            operationType: 'hotel_review_create',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $review = $this->db->transaction(function () use (
            $hotelId,
            $rating,
            $title,
            $content,
            $guestId,
            $tenantId,
            $categories,
            $correlationId,
        ) {
            return Review::create([
                'tenant_id'        => $tenantId,
                'hotel_id'         => $hotelId,
                'guest_id'         => $guestId,
                'rating'           => $rating,
                'title'            => $title,
                'content'          => $content,
                'categories'       => $categories,
                'verified_booking' => true,
                'published_at'     => Carbon::now(),
                'correlation_id'   => $correlationId,
            ]);
        });

        $this->audit->log(
            action: 'hotel_review_created',
            subjectType: Review::class,
            subjectId: $review->id,
            old: [],
            new: $review->toArray(),
            correlationId: $correlationId,
        );

        $this->logger->info('Review created', [
            'review_id'      => $review->id,
            'hotel_id'       => $hotelId,
            'rating'         => $rating,
            'correlation_id' => $correlationId,
        ]);

        $this->recalculateHotelRating($hotelId, $correlationId);

        return $review;
    }

    /**
     * Пересчитать средний рейтинг отеля.
     */
    public function recalculateHotelRating(int $hotelId, string $correlationId): float
    {
        $avgRating = Review::where('hotel_id', $hotelId)
            ->whereNotNull('published_at')
            ->avg('rating') ?? 0.0;

        $avgRating = round((float) $avgRating, 2);

        $hotel = Hotel::findOrFail($hotelId);
        $oldRating = $hotel->rating;

        $hotel->update([
            'rating' => $avgRating,
        ]);

        $this->audit->log(
            action: 'hotel_rating_recalculated',
            subjectType: Hotel::class,
            subjectId: $hotelId,
            old: ['rating' => $oldRating],
            new: ['rating' => $avgRating],
            correlationId: $correlationId,
        );

        $this->logger->info('Hotel rating updated', [
            'hotel_id'       => $hotelId,
            'old_rating'     => $oldRating,
            'new_rating'     => $avgRating,
            'correlation_id' => $correlationId,
        ]);

        return $avgRating;
    }
}
