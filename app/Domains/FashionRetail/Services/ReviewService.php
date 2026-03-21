<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use App\Domains\FashionRetail\Models\FashionRetailReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ReviewService
{
    public function getProductReviews(int $productId): Collection
    {
        return FashionRetailReview::where('product_id', $productId)
            ->where('status', 'approved')
            ->orderBy('helpful_count', 'desc')
            ->with('user')
            ->get();
    }

    public function getShopReviews(int $shopId): Collection
    {
        return FashionRetailReview::whereHas('product', function ($query) use ($shopId) {
            $query->where('shop_id', $shopId);
        })
            ->where('status', 'approved')
            ->orderBy('helpful_count', 'desc')
            ->get();
    }

    public function getPendingReviews(): Collection
    {
        return FashionRetailReview::where('status', 'pending')
            ->with('product', 'user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function approveReview(int $reviewId, string $correlationId): void
    {
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
