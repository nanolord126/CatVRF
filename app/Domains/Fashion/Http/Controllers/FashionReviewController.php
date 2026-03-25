<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionReview;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionReviewController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function getProductReviews(int $id): JsonResponse
    {
        try {
            $reviews = FashionReview::where('product_id', $id)
                ->where('status', 'approved')
                ->with('reviewer')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $this->db->transaction(function () use ($correlationId) {
                FashionReview::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'product_id' => request('product_id'),
                    'reviewer_id' => auth()->id(),
                    'order_id' => request('order_id'),
                    'rating' => request('rating'),
                    'comment' => request('comment'),
                    'verified_purchase' => true,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                $this->log->channel('audit')->info('Fashion review submitted', [
                    'product_id' => request('product_id'),
                    'reviewer_id' => auth()->id(),
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $review = FashionReview::findOrFail($id);

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->update([...request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Fashion review updated', ['review_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $review = FashionReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->delete();
                $this->log->channel('audit')->info('Fashion review deleted', ['review_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function markHelpful(int $id): JsonResponse
    {
        try {
            $review = FashionReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->increment('helpful_count');
                $this->log->channel('audit')->info('Fashion review marked helpful', ['review_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $reviews = FashionReview::with('product', 'reviewer')->paginate(50);
            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $review = FashionReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->update(['status' => 'approved', 'correlation_id' => $correlationId]);
                $this->log->channel('audit')->info('Fashion review approved', ['review_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $review = FashionReview::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->delete();
                $this->log->channel('audit')->info('Fashion review rejected', ['review_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
