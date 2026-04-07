<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionRetailReviewController extends Controller
{

    public function __construct(private readonly ReviewService $reviewService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(int $productId): JsonResponse
        {
            try {
                $reviews = FashionRetailReview::where('product_id', $productId)
                    ->where('status', 'approved')
                    ->with('user')
                    ->paginate(20);

                $correlationId = Str::uuid()->toString();
                $this->logger->info('FashionRetail reviews listed', [
                    'product_id' => $productId,
                    'count' => $reviews->count(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail review listing failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(int $productId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = $this->db->transaction(function () use ($productId, $correlationId) {
                    return FashionRetailReview::create([
                        'uuid' => Str::uuid(),
                        'product_id' => $productId,
                        'user_id' => $request->user()?->id,
                        'order_id' => $request->input('order_id'),
                        'rating' => $request->input('rating'),
                        'title' => $request->input('title'),
                        'comment' => $request->input('comment'),
                        'images' => $request->input('images', []),
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail review created', [
                    'review_id' => $review->id,
                    'product_id' => $productId,
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail review creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function destroy(int $productId, int $reviewId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = FashionRetailReview::findOrFail($reviewId);

                if ($review->user_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                    $review->delete();
                });

                $this->logger->info('FashionRetail review deleted', [
                    'review_id' => $reviewId,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Review deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail review deletion failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
