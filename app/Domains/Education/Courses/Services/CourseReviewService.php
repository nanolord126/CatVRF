<?php declare(strict_types=1);

/**
 * CourseReviewService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/coursereviewservice
 */


namespace App\Domains\Education\Courses\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class CourseReviewService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createReview(array $data, string $correlationId): CourseReview
        {
            return $this->db->transaction(function () use ($data, $correlationId) {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'course_review_create', amount: 0, correlationId: $correlationId ?? '');

                $review = CourseReview::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                $this->logger->info('Course review created', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
