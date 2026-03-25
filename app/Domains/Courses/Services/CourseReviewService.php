declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Courses\Services;

use App\Domains\Courses\Models\CourseReview;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * CourseReviewService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CourseReviewService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    public function createReview(array $data, string $correlationId): CourseReview
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'course_review_create');

            $review = CourseReview::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->log->channel('audit')->info('Course review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }
}
