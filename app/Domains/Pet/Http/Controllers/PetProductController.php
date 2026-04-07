<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PetProductController extends Controller
{

    public function __construct(
            private readonly ProductService $productService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $products = PetProduct::where('is_active', true)
                    ->with('clinic')
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get products', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve products',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function show($id): JsonResponse
        {
            try {
                $product = PetProduct::with('clinic')->findOrFail($id);

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

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $clinic = $request->user()->clinics()->findOrFail($request->clinic_id);

                $product = $this->productService->createProduct($clinic, $request->validated(), $correlationId);

                $this->logger->info('Pet product created', [
                    'correlation_id' => $correlationId,
                    'product_id'     => $product->id ?? null,
                    'tenant_id'      => $product->tenant_id ?? null,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create product', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create product',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = PetProduct::findOrFail($id);
                $this->authorize('update', $product);

                $product = $this->productService->updateProduct($product, $request->validated(), $correlationId);

                $this->logger->info('Pet product updated', [
                    'correlation_id' => $correlationId,
                    'product_id'     => $product->id,
                    'tenant_id'      => $product->tenant_id ?? null,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update product',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy($id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $product = PetProduct::findOrFail($id);
                $this->authorize('delete', $product);

                $product->delete();

                $this->logger->info('Pet product deleted', [
                    'correlation_id' => $correlationId,
                    'product_id'     => $product->id,
                    'tenant_id'      => $product->tenant_id ?? null,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Product deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete product',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function search(Request $request): JsonResponse
        {
            try {
                $query = PetProduct::query();

                if ($request->filled('name')) {
                    $query->where('name', 'like', '%' . $request->name . '%');
                }

                if ($request->filled('pet_type')) {
                    $query->where('pet_type', $request->pet_type);
                }

                if ($request->filled('category')) {
                    $query->where('category', $request->category);
                }

                $products = $query->where('is_active', true)->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Search failed',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
