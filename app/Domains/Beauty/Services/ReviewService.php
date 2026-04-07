<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautySalon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\Review;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ReviewService — создание отзывов и пересчёт рейтингов мастеров/салонов.
 *
 * CANON 2026: FraudControlService::check() + DB::transaction() + correlation_id + AuditService.
 * Никаких фасадов, только constructor injection. recalculateRatings() полностью реализован.
 */
final readonly class ReviewService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Оставить отзыв после завершённого визита.
     *
     * 1. Проверяем что appointment завершён
     * 2. Проверяем что отзыв ещё не оставлен
     * 3. Fraud-check
     * 4. Создаём отзыв
     * 5. Пересчитываем рейтинги мастера и салона
     * 6. Audit log
     */
    public function createReview(
        Appointment $appointment,
        array $data,
        string $correlationId = '',
    ): Review {
        $correlationId = $correlationId !== '' ? $correlationId : Str::uuid()->toString();

        if ($appointment->status !== 'completed') {
            throw new \DomainException(
                "Отзыв можно оставить только для завершённой записи. Текущий статус: {$appointment->status}"
            );
        }

        $existingReview = Review::where('appointment_id', $appointment->id)->first();

        if ($existingReview !== null) {
            throw new \DomainException(
                "Отзыв для записи #{$appointment->id} уже существует (review_id={$existingReview->id})."
            );
        }

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'beauty_create_review',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($appointment, $data, $correlationId): Review {
            $review = Review::create(array_merge($data, [
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'salon_id' => $appointment->salon_id,
                'master_id' => $appointment->master_id,
                'tenant_id' => $appointment->tenant_id,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
            ]));

            $this->recalculateMasterRating($appointment->master_id);
            $this->recalculateSalonRating($appointment->salon_id);

            $this->audit->record(
                action: 'beauty_review_created',
                subjectType: Review::class,
                subjectId: $review->id,
                oldValues: [],
                newValues: $review->toArray(),
                correlationId: $correlationId,
            );

            $this->logger->info('Beauty review created', [
                'review_id' => $review->id,
                'appointment_id' => $appointment->id,
                'master_id' => $appointment->master_id,
                'salon_id' => $appointment->salon_id,
                'rating' => $review->rating,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }

    /**
     * Пересчитать средний рейтинг мастера по всем его отзывам.
     *
     * AVG(rating) по всем Review с master_id — обновляем поле Master.rating.
     */
    private function recalculateMasterRating(int $masterId): void
    {
        $master = Master::find($masterId);

        if ($master === null) {
            return;
        }

        $averageRating = Review::where('master_id', $masterId)
            ->avg('rating');

        $reviewCount = Review::where('master_id', $masterId)
            ->count();

        $master->update([
            'rating' => round((float) ($averageRating ?? 0), 2),
            'reviews_count' => $reviewCount,
        ]);

        $this->logger->debug('Master rating recalculated', [
            'master_id' => $masterId,
            'new_rating' => $averageRating,
            'reviews_count' => $reviewCount,
        ]);
    }

    /**
     * Пересчитать средний рейтинг салона по всем его отзывам.
     *
     * AVG(rating) по всем Review с salon_id — обновляем поле BeautySalon.rating.
     */
    private function recalculateSalonRating(int $salonId): void
    {
        $salon = BeautySalon::find($salonId);

        if ($salon === null) {
            return;
        }

        $averageRating = Review::where('salon_id', $salonId)
            ->avg('rating');

        $reviewCount = Review::where('salon_id', $salonId)
            ->count();

        $salon->update([
            'rating' => round((float) ($averageRating ?? 0), 2),
            'reviews_count' => $reviewCount,
        ]);

        $this->logger->debug('Salon rating recalculated', [
            'salon_id' => $salonId,
            'new_rating' => $averageRating,
            'reviews_count' => $reviewCount,
        ]);
    }

    /**
     * Получить отзывы мастера (с пагинацией).
     */
    public function getMasterReviews(int $masterId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Review::where('master_id', $masterId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Получить отзывы салона (с пагинацией).
     */
    public function getSalonReviews(int $salonId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Review::where('salon_id', $salonId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
