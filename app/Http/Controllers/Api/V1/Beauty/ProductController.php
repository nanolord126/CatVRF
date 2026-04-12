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
 * Beauty Product API Controller — косметика и товары салонов.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * GET /products — список товаров (публичный, через apiResource).
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $query = $this->db->table('beauty_products');

            if ($request->filled('salon_id')) {
                $query->where('salon_id', (int) $request->input('salon_id'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }

            $products = $query->orderBy('name')
                ->paginate((int) $request->input('per_page', 20));

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Products list failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * GET /products/{id} — детали товара.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $product = $this->db->table('beauty_products')->where('id', $id)->first();

            if ($product === null) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return $this->response->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => $product,
            ], 200);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Product show failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to retrieve product',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * POST /products — создание товара (auth + manage-beauty-business).
     */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($request, $correlationId): JsonResponse {
                $this->fraudService->scoreOperation([
                    'type' => 'product_create',
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);

                $productId = $this->db->table('beauty_products')->insertGetId([
                    'tenant_id' => (int) $request->header('X-Tenant-ID', '0'),
                    'salon_id' => $request->integer('salon_id'),
                    'uuid' => Str::uuid()->toString(),
                    'correlation_id' => $correlationId,
                    'name' => $request->input('name'),
                    'category' => $request->input('category', 'cosmetics'),
                    'price' => $request->integer('price'),
                    'quantity' => $request->integer('quantity', 0),
                    'description' => $request->input('description', ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->logger->channel('audit')->info('Beauty product created', [
                    'correlation_id' => $correlationId,
                    'product_id' => $productId,
                    'user_id' => auth()->id(),
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Product created',
                    'correlation_id' => $correlationId,
                    'data' => ['id' => $productId],
                ], 201);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Product creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to create product',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * PUT /products/{product} — обновление товара.
     */
    public function update(int $product, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($product, $request, $correlationId): JsonResponse {
                $updated = $this->db->table('beauty_products')
                    ->where('id', $product)
                    ->update(array_filter([
                        'name' => $request->input('name'),
                        'price' => $request->input('price'),
                        'quantity' => $request->input('quantity'),
                        'description' => $request->input('description'),
                        'correlation_id' => $correlationId,
                        'updated_at' => now(),
                    ]));

                if ($updated === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Product not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Beauty product updated', [
                    'correlation_id' => $correlationId,
                    'product_id' => $product,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Product updated',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Product update failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to update product',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * DELETE /products/{product} — удаление товара.
     */
    public function destroy(int $product, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            return $this->db->transaction(function () use ($product, $correlationId): JsonResponse {
                $deleted = $this->db->table('beauty_products')->where('id', $product)->delete();

                if ($deleted === 0) {
                    return $this->response->json([
                        'success' => false,
                        'message' => 'Product not found',
                        'correlation_id' => $correlationId,
                    ], 404);
                }

                $this->logger->channel('audit')->info('Beauty product deleted', [
                    'correlation_id' => $correlationId,
                    'product_id' => $product,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Product deleted',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Product deletion failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            return $this->response->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
