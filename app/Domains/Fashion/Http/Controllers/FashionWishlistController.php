<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionWishlistController extends Controller
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}


    public function index(): JsonResponse
        {
            try {
                $wishlist = FashionWishlist::where('user_id', $request->user()?->id)
                    ->with('product')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $wishlist, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function a(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($id, $correlationId) {
                    FashionWishlist::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'user_id' => $request->user()?->id,
                        'product_id' => $id,
                        'color' => $request->input('color'),
                        'size' => $request->input('size'),
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Product added to wishlist', [
                        'product_id' => $id,
                        'user_id' => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function remove(int $id): JsonResponse
        {
            try {
                $wishlist = FashionWishlist::where('product_id', $id)
                    ->where('user_id', $request->user()?->id)
                    ->firstOrFail();
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($wishlist, $correlationId) {
                    $wishlist->delete();
                    $this->logger->info('Product removed from wishlist', [
                        'product_id' => $wishlist->product_id,
                        'user_id' => $request->user()?->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
