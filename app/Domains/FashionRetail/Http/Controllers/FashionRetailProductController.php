<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Http\Controllers;

use App\Domains\FashionRetail\Models\FashionRetailProduct;
use App\Domains\FashionRetail\Models\FashionRetailCategory;
use App\Domains\FashionRetail\Services\ProductService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionRetailProductController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $products = FashionRetailProduct::where('status', 'active')
                ->with('shop', 'category', 'variants')
                ->paginate(20);

            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('FashionRetail products listed', [
                'count' => $products->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail product listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $product = FashionRetailProduct::with('shop', 'category', 'variants', 'reviews', 'orders')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $product,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $product = DB::transaction(function () use ($correlationId) {
                return FashionRetailProduct::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'business_group_id' => filament()->getTenant()->business_group_id,
                    'shop_id' => request('shop_id'),
                    'category_id' => request('category_id'),
                    'name' => request('name'),
                    'description' => request('description'),
                    'sku' => request('sku'),
                    'barcode' => request('barcode'),
                    'price' => request('price'),
                    'cost_price' => request('cost_price'),
                    'discount_percent' => request('discount_percent', 0),
                    'current_stock' => request('current_stock', 0),
                    'min_stock_threshold' => request('min_stock_threshold', 10),
                    'colors' => request('colors', []),
                    'sizes' => request('sizes', []),
                    'images' => request('images', []),
                    'supplier_id' => request('supplier_id'),
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail product created', [
                'product_id' => $product->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail product creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $product = FashionRetailProduct::findOrFail($id);

            DB::transaction(function () use ($product, $correlationId) {
                $product->update([
                    'name' => request('name', $product->name),
                    'description' => request('description', $product->description),
                    'price' => request('price', $product->price),
                    'discount_percent' => request('discount_percent', $product->discount_percent),
                    'current_stock' => request('current_stock', $product->current_stock),
                    'status' => request('status', $product->status),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail product updated', [
                'product_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail product update failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $product = FashionRetailProduct::findOrFail($id);

            DB::transaction(function () use ($product, $correlationId) {
                $product->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                $product->delete();
            });

            Log::channel('audit')->info('FashionRetail product deleted', [
                'product_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail product deletion failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
