<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function getProductReviews(int $productId): Collection
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailReview::where('product_id', $productId)
                ->where('status', 'approved')
                ->orderBy('helpful_count', 'desc')
                ->with('user')
                ->get();
        }

        public function getShopReviews(int $shopId): Collection
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailReview::whereHas('product', function ($query) use ($shopId) {
                $query->where('shop_id', $shopId);
            })
                ->where('status', 'approved')
                ->orderBy('helpful_count', 'desc')
                ->get();
        }

        public function getPendingReviews(): Collection
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            return FashionRetailReview::where('status', 'pending')
                ->with('product', 'user')
                ->orderBy('created_at', 'asc')
                ->get();
        }

        public function approveReview(int $reviewId, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            DB::transaction(function () use ($reviewId, $correlationId) {
                $review = FashionRetailReview::lockForUpdate()->findOrFail($reviewId);

                $review->update([
                    'status' => 'approved',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('FashionRetail review approved', [
                    'review_id' => $reviewId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function rejectReview(int $reviewId, string $correlationId): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            DB::transaction(function () use ($reviewId, $correlationId) {
                $review = FashionRetailReview::lockForUpdate()->findOrFail($reviewId);

                $review->update([
                    'status' => 'rejected',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('FashionRetail review rejected', [
                    'review_id' => $reviewId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        public function updateProductRating(int $productId): void
        {
            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

            DB::transaction(function () use ($productId) {
                $reviews = FashionRetailReview::where('product_id', $productId)
                    ->where('status', 'approved')
                    ->get();

                if ($reviews->isEmpty()) {
                    return;
                }

                $avgRating = $reviews->avg('rating');
                $count = $reviews->count();

                FashionRetailReview::where('product_id', $productId)
                    ->update([
                        'rating' => $avgRating,
                        'review_count' => $count,
                    ]);
            });
        }
}
