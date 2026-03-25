declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use App\Domains\Flowers\Models\FlowerReview;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final /**
 * FlowerReviewService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerReviewService
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

    public function createReview(array $data, string $correlationId): FlowerReview
    {
        return $this->db->transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'review_create');

            $review = FlowerReview::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            $this->log->channel('audit')->info('Flower review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }
}
