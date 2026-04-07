<?php declare(strict_types=1);

/**
 * ApartmentReviewService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/apartmentreviewservice
 */


namespace App\Domains\ShortTermRentals\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class ApartmentReviewService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createReview(array $data, string $correlationId): ApartmentReview
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'apartment_review_create', amount: 0, correlationId: $correlationId ?? '');

                $review = ApartmentReview::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Apartment review created', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
