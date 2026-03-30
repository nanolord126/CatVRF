<?php declare(strict_types=1);

namespace App\Domains\Flowers\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerReviewService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControl
        ) {}

        public function createReview(array $data, string $correlationId): FlowerReview
        {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check($data, 'review_create');

                $review = FlowerReview::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                ]));

                Log::channel('audit')->info('Flower review created', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });
        }
}
