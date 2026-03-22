<?php declare(strict_types=1);

namespace App\Domains\Courses\Services;

use App\Domains\Courses\Models\CourseReview;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CourseReviewService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function createReview(array $data, string $correlationId): CourseReview
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'course_review_create');

            $review = CourseReview::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Course review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }
}
