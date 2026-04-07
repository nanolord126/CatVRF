<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\FlowerReview;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerReviewController extends Controller
{
    public function __construct(
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    public function store(int $orderId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $order = FlowerOrder::query()->findOrFail($orderId);

            if ($order->user_id !== $request->user()?->id) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $validated = $request->validate([
                'quality_rating' => 'required|integer|min:1|max:5',
                'delivery_rating' => 'required|integer|min:1|max:5',
                'freshness_rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $review = $this->db->transaction(function () use ($order, $validated, $correlationId, $request) {
                $overallRating = round(
                    ($validated['quality_rating'] + $validated['delivery_rating'] + $validated['freshness_rating']) / 3,
                    1
                );

                $review = FlowerReview::query()->create([
                    'tenant_id' => $order->tenant_id,
                    'order_id' => $order->id,
                    'shop_id' => $order->shop_id,
                    'user_id' => $request->user()?->id,
                    'quality_rating' => $validated['quality_rating'],
                    'delivery_rating' => $validated['delivery_rating'],
                    'freshness_rating' => $validated['freshness_rating'],
                    'overall_rating' => $overallRating,
                    'comment' => $validated['comment'] ?? null,
                    'verified_purchase' => true,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Flower review created', [
                    'review_id' => $review->id,
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Review creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function shopReviews(int $shopId): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $reviews = FlowerReview::query()
                ->where('shop_id', $shopId)
                ->where('status', 'approved')
                ->with('user')
                ->paginate(15);

            return new JsonResponse([
                'success' => true,
                'data' => $reviews,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $review = FlowerReview::query()->findOrFail($id);

            if ($review->user_id !== $request->user()?->id) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $validated = $request->validate([
                'quality_rating' => 'integer|min:1|max:5',
                'delivery_rating' => 'integer|min:1|max:5',
                'freshness_rating' => 'integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $review = $this->db->transaction(function () use ($review, $validated, $correlationId) {
                $review->update([...$validated, 'correlation_id' => $correlationId]);

                $this->logger->info('Flower review updated', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);

                return $review;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $review,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $review = FlowerReview::query()->findOrFail($id);

            if ($review->user_id !== $request->user()?->id) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->db->transaction(function () use ($review, $correlationId) {
                $review->delete();

                $this->logger->info('Flower review deleted', [
                    'review_id' => $review->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return new JsonResponse([
                'success' => true,
                'message' => 'Review deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
