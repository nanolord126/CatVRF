<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionProductController extends Controller
{

    public function __construct(private readonly ProductService $productService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $products = FashionProduct::where('status', 'active')
                    ->with('store', 'category')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $product = FashionProduct::with('store', 'category', 'variants', 'reviews')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $product, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Product not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function reviews(int $id): JsonResponse
        {
            try {
                $product = FashionProduct::findOrFail($id);
                $reviews = $product->reviews()->where('status', 'approved')->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function categories(): JsonResponse
        {
            try {
                $categories = FashionCategory::where('is_active', true)->get();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $categories, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function search(): JsonResponse
        {
            try {
                $query = $request->input('q');
                $products = FashionProduct::where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = $this->productService->createProduct(
                    tenant()->id,
                    $request->input('store_id'),
                    $request->input('category_id'),
                    $request->input('name'),
                    $request->input('sku'),
                    $request->input('price'),
                    $request->input('stock'),
                    $request->input('colors', []),
                    $request->input('sizes', []),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $product, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = FashionProduct::findOrFail($id);

                $this->productService->updateProduct($product, $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $product = FashionProduct::findOrFail($id);

                $this->db->transaction(function () use ($product, $id, $correlationId) {
                    $product->delete();
                    $this->logger->info('Fashion product deleted', ['product_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateStock(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $product = FashionProduct::findOrFail($id);

                $this->productService->updateStock($product, $request->input('quantity'), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $products = FashionProduct::with('store')->paginate(50);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $totalProducts = FashionProduct::count();
                $activeProducts = FashionProduct::where('status', 'active')->count();
                $avgRating = FashionProduct::avg('rating');
                $lowStockProducts = FashionProduct::whereColumn('current_stock', '<', 'min_stock_threshold')->count();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'total_products' => $totalProducts,
                        'active_products' => $activeProducts,
                        'avg_rating' => round($avgRating, 2),
                        'low_stock' => $lowStockProducts,
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
