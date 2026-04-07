<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionRetailProductController extends Controller
{

    public function __construct(private readonly ProductService $productService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $products = FashionRetailProduct::where('status', 'active')
                    ->with('shop', 'category', 'variants')
                    ->paginate(20);

                $correlationId = Str::uuid()->toString();
                $this->logger->info('FashionRetail products listed', [
                    'count' => $products->count(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail product listing failed', [
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

        public function show(int $id): JsonResponse
        {
            try {
                $product = FashionRetailProduct::with('shop', 'category', 'variants', 'reviews', 'orders')->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Product not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = $this->db->transaction(function () use ($correlationId) {
                    return FashionRetailProduct::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'business_group_id' => tenant()->business_group_id,
                        'shop_id' => $request->input('shop_id'),
                        'category_id' => $request->input('category_id'),
                        'name' => $request->input('name'),
                        'description' => $request->input('description'),
                        'sku' => $request->input('sku'),
                        'barcode' => $request->input('barcode'),
                        'price' => $request->input('price'),
                        'cost_price' => $request->input('cost_price'),
                        'discount_percent' => $request->input('discount_percent', 0),
                        'current_stock' => $request->input('current_stock', 0),
                        'min_stock_threshold' => $request->input('min_stock_threshold', 10),
                        'colors' => $request->input('colors', []),
                        'sizes' => $request->input('sizes', []),
                        'images' => $request->input('images', []),
                        'supplier_id' => $request->input('supplier_id'),
                        'status' => 'active',
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail product created', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail product creation failed', [
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

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = FashionRetailProduct::findOrFail($id);

                $this->db->transaction(function () use ($product, $correlationId) {
                    $product->update([
                        'name' => $request->input('name', $product->name),
                        'description' => $request->input('description', $product->description),
                        'price' => $request->input('price', $product->price),
                        'discount_percent' => $request->input('discount_percent', $product->discount_percent),
                        'current_stock' => $request->input('current_stock', $product->current_stock),
                        'status' => $request->input('status', $product->status),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail product updated', [
                    'product_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail product update failed', [
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

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = FashionRetailProduct::findOrFail($id);

                $this->db->transaction(function () use ($product, $correlationId) {
                    $product->update(['status' => 'deleted', 'correlation_id' => $correlationId]);
                    $product->delete();
                });

                $this->logger->info('FashionRetail product deleted', [
                    'product_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Product deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail product deletion failed', [
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
