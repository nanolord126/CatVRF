<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class ReviewController extends Controller
{


public function __construct(
        private ReviewService $reviewService,
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
    ) {
    }

        public function index(int $serviceId): JsonResponse
        {
            try {
                $reviews = Review::where('service_id', $serviceId)
                    ->where('status', 'approved')
                    ->with('user')
                    ->paginate(20);

                $correlationId = Str::uuid()->toString();
                $this->logger->info('Beauty reviews listed', [
                    'service_id' => $serviceId,
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
                $this->logger->error('Beauty review listing failed', [
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

        public function store(int $serviceId, \Illuminate\Http\Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_create', amount: 0, correlationId: $correlationId);

            try {

                $review = $this->db->transaction(function () use ($serviceId, $correlationId) {
                    return Review::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'service_id' => $serviceId,
                        'user_id' => $request->user()?->id,
                        'appointment_id' => $request->input('appointment_id'),
                        'rating' => $request->input('rating'),
                        'title' => $request->input('title'),
                        'comment' => $request->input('comment'),
                        'images' => $request->input('images', []),
                        'status' => 'pending',
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('Beauty review created', [
                    'review_id' => $review->id,
                    'service_id' => $serviceId,
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
                $this->logger->error('Beauty review creation failed', [
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

        public function destroy(int $serviceId, int $reviewId, \Illuminate\Http\Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'review_delete', amount: 0, correlationId: $correlationId);

            try {
                $review = Review::findOrFail($reviewId);

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

                $this->logger->info('Beauty review deleted', [
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
                $this->logger->error('Beauty review deletion failed', [
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
