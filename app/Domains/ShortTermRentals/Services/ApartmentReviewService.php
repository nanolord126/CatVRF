<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Services;

use App\Domains\ShortTermRentals\Models\ApartmentReview;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ApartmentReviewService
{
    public function __construct(
        private readonly FraudControlService $fraudControl
    ) {}

    public function createReview(array $data, string $correlationId): ApartmentReview
    {
        return DB::transaction(function () use ($data, $correlationId) {
            $this->fraudControl->check($data, 'apartment_review_create');

            $review = ApartmentReview::create(array_merge($data, [
                'correlation_id' => $correlationId,
            ]));

            Log::channel('audit')->info('Apartment review created', [
                'review_id' => $review->id,
                'correlation_id' => $correlationId,
            ]);

            return $review;
        });
    }
}
