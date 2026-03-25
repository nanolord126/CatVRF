<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\FashionRetail\Models\FashionRetailReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class ReviewService
{
    public function getProductReviews(int $productId): Collection
    {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailReview::where('product_id', $productId)
            ->where('status', 'approved')
            ->orderBy('helpful_count', 'desc')
            ->with('user')
            ->get();
    }

    public function getShopReviews(int $shopId): Collection
    {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

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
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        return FashionRetailReview::where('status', 'pending')
            ->with('product', 'user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function approveReview(int $reviewId, string $correlationId): void
    {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        $this->db->transaction(function () use ($reviewId, $correlationId) {
            $review = FashionRetailReview::lockForUpdate()->findOrFail($reviewId);

            $review->update([
                'status' => 'approved',
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('FashionRetail review approved', [
                'review_id' => $reviewId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function rejectReview(int $reviewId, string $correlationId): void
    {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        $this->db->transaction(function () use ($reviewId, $correlationId) {
            $review = FashionRetailReview::lockForUpdate()->findOrFail($reviewId);

            $review->update([
                'status' => 'rejected',
                'correlation_id' => $correlationId,
            ]);

            $this->log->channel('audit')->info('FashionRetail review rejected', [
                'review_id' => $reviewId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function updateProductRating(int $productId): void
    {
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in FashionRetail', ['correlation_id' => $correlationId]);

        $this->db->transaction(function () use ($productId) {
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
