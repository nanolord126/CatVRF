<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionReviewController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function getProductReviews(int $id): JsonResponse
        {
            try {
                $reviews = FashionReview::where('product_id', $id)
                    ->where('status', 'approved')
                    ->with('reviewer')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->db->transaction(function () use ($correlationId) {
                    FashionReview::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'product_id' => $request->input('product_id'),
                        'reviewer_id' => $request->user()?->id,
                        'order_id' => $request->input('order_id'),
                        'rating' => $request->input('rating'),
                        'comment' => $request->input('comment'),
                        'verified_purchase' => true,
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Fashion review submitted', [
                        'product_id' => $request->input('product_id'),
                        'reviewer_id' => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = FashionReview::findOrFail($id);

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->update([...$request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                    $this->logger->info('Fashion review updated', ['review_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $review = FashionReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->delete();
                    $this->logger->info('Fashion review deleted', ['review_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function markHelpful(int $id): JsonResponse
        {
            try {
                $review = FashionReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->increment('helpful_count');
                    $this->logger->info('Fashion review marked helpful', ['review_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $reviews = FashionReview::with('product', 'reviewer')->paginate(50);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function approve(int $id): JsonResponse
        {
            try {
                $review = FashionReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->update(['status' => 'approved', 'correlation_id' => $correlationId]);
                    $this->logger->info('Fashion review approved', ['review_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $review, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function reject(int $id): JsonResponse
        {
            try {
                $review = FashionReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($review, $correlationId) {
                    $review->delete();
                    $this->logger->info('Fashion review rejected', ['review_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
