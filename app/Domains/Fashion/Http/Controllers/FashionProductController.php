<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionCategory;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Services\ProductService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionProductController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $products = FashionProduct::where('status', 'active')
                ->with('store', 'category')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $product = FashionProduct::with('store', 'category', 'variants', 'reviews')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $product, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Product not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function reviews(int $id): JsonResponse
    {
        try {
            $product = FashionProduct::findOrFail($id);
            $reviews = $product->reviews()->where('status', 'approved')->paginate(20);
            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = FashionCategory::where('is_active', true)->get();
            return response()->json(['success' => true, 'data' => $categories, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function search(): JsonResponse
    {
        try {
            $query = request('q');
            $products = FashionProduct::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $product = $this->productService->createProduct(
                tenant('id'),
                request('store_id'),
                request('category_id'),
                request('name'),
                request('sku'),
                request('price'),
                request('stock'),
                request('colors', []),
                request('sizes', []),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $product = FashionProduct::findOrFail($id);

            $this->productService->updateProduct($product, request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), $correlationId);

            return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $product = FashionProduct::findOrFail($id);

            DB::transaction(function () use ($product, $id, $correlationId) {
                $product->delete();
                Log::channel('audit')->info('Fashion product deleted', ['product_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function updateStock(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        try {
            $product = FashionProduct::findOrFail($id);

            $this->productService->updateStock($product, request('quantity'), $correlationId);

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $products = FashionProduct::with('store')->paginate(50);
            return response()->json(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $totalProducts = FashionProduct::count();
            $activeProducts = FashionProduct::where('status', 'active')->count();
            $avgRating = FashionProduct::avg('rating');
            $lowStockProducts = FashionProduct::whereColumn('current_stock', '<', 'min_stock_threshold')->count();

            return response()->json([
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
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
