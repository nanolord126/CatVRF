<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Beauty;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

/**
 * Beauty Review API Controller — отзывы на салоны/мастеров.
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * POST /reviews — создать отзыв (auth).
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'review_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                if (($fraudResult['decision'] ?? '') === 'block') {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Review blocked by fraud control',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $reviewId = $this->db->table('beauty_reviews')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'user_id' => auth()->id(),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'reviewable_type' => $request->input('reviewable_type', 'salon'),
                    'reviewable_id' => $request->integer('reviewable_id'),
                    'rating' => $request->integer('rating'),
                    'comment' => $request->input('comment', ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Beauty review created', [
                    'correlation_id' => $correlationId,
                    'review_id' => $reviewId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Review submitted',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $reviewId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Review creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to submit review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /reviews/{id} — обновить свой отзыв.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $request, $correlationId): JsonResponse {
                $review = $this->db->table('beauty_reviews')
                    ->where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();

                if ($review === null) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Review not found or not yours',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->db->table('beauty_reviews')->where('id', $id)->update(array_filter([
                    'rating' => $request->input('rating'),
                    'comment' => $request->input('comment'),
                    'correlation_id' => $correlationId,
                    'updated_at' => now(),
                ]));

                $this->logger->channel('audit')->info('Beauty review updated', [
                    'correlation_id' => $correlationId,
                    'review_id' => $id,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Review updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Review update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /reviews/{id} — удалить свой отзыв.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($id, $correlationId): JsonResponse {
                $deleted = $this->db->table('beauty_reviews')
                    ->where('id', $id)
                    ->where('user_id', auth()->id())
                    ->delete();

                if ($deleted === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Review not found or not yours',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Beauty review deleted', [
                    'correlation_id' => $correlationId,
                    'review_id' => $id,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Review deleted',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Review deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete review',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
