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

/**
 * Beauty Wishlist API Controller — избранные салоны/мастера/услуги.
 */
final class WishlistController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * POST /wishlist/add/{type}/{id} — добавить в избранное.
     */
    public function add(string $type, int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $exists = $this->db->table('beauty_wishlists')
                ->where('user_id', auth()->id())
                ->where('wishable_type', $type)
                ->where('wishable_id', $id)
                ->exists();

            if ($exists) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Already in wishlist',
                    'correlation_id' => $correlationId,
                ], 409);
            }

            $this->db->table('beauty_wishlists')->insert([
                'user_id' => auth()->id(),
                'wishable_type' => $type,
                'wishable_id' => $id,
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logger->channel('audit')->info('Added to beauty wishlist', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
                'type' => $type,
                'id' => $id,
            ]);

            return $this->response->json([
                'success' => true,
                'message' => 'Added to wishlist',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wishlist add failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to add to wishlist',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /wishlist/remove/{type}/{id} — убрать из избранного.
     */
    public function remove(string $type, int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $deleted = $this->db->table('beauty_wishlists')
                ->where('user_id', auth()->id())
                ->where('wishable_type', $type)
                ->where('wishable_id', $id)
                ->delete();

            if ($deleted === 0) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Not in wishlist',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            $this->logger->channel('audit')->info('Removed from beauty wishlist', [
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
                'type' => $type,
                'id' => $id,
            ]);

            return $this->response->json([
                'success' => true,
                'message' => 'Removed from wishlist',
                'correlation_id' => $correlationId,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wishlist remove failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to remove from wishlist',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /wishlist — список избранного.
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $items = $this->db->table('beauty_wishlists')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $items,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Wishlist list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve wishlist',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
