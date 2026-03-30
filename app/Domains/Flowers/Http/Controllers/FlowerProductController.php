<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerProductController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $products = FlowerProduct::query()
                    ->where('is_available', true)
                    ->when($request->shop_id, fn ($q) => $q->where('shop_id', $request->shop_id))
                    ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->with('shop')
                    ->paginate(15);

                Log::channel('audit')->info('Flower products listed', [
                    'count' => $products->count(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Product listing failed', [
                    'error' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        public function show(int $id): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $product = FlowerProduct::query()
                    ->where('id', $id)
                    ->where('is_available', true)
                    ->with('shop')
                    ->firstOrFail();

                Log::channel('audit')->info('Flower product viewed', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_NOT_FOUND);
            }
        }

        public function shopProducts(int $shopId): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shop = FlowerShop::query()->findOrFail($shopId);
                $products = $shop->products()->where('is_available', true)->paginate(15);

                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_NOT_FOUND);
            }
        }

        public function search(Request $request): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $query = $request->get('q', '');
                $products = FlowerProduct::query()
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->where('is_available', true)
                    ->limit(20)
                    ->get();

                Log::channel('audit')->info('Flower products searched', [
                    'query' => $query,
                    'results' => $products->count(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $products,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'product_type' => 'required|in:bouquet,arrangement,basket,single_flower,subscription,gift',
                    'price' => 'required|numeric|min:1',
                    'stock' => 'required|integer|min:0',
                ]);

                $shop = auth()->user()->flowerShop;
                if (!$shop) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Flower shop not found',
                        'correlation_id' => $correlationId,
                    ], $this->response->HTTP_FORBIDDEN);
                }

                $product = DB::transaction(function () use ($validated, $shop, $correlationId) {
                    $product = FlowerProduct::query()->create([
                        'tenant_id' => filament()->getTenant()->id,
                        'shop_id' => $shop->id,
                        'correlation_id' => $correlationId,
                        ...$validated,
                    ]);

                    Log::channel('audit')->info('Flower product created', [
                        'product_id' => $product->id,
                        'shop_id' => $shop->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $product;
                });

                return response()->json([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_CREATED);
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Product creation failed', [
                    'error' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        public function update(int $id, Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $product = FlowerProduct::query()->findOrFail($id);

                $this->authorize('update', $product);

                $validated = $request->validate([
                    'name' => 'string|max:255',
                    'description' => 'nullable|string',
                    'price' => 'numeric|min:1',
                    'stock' => 'integer|min:0',
                ]);

                $product = DB::transaction(function () use ($product, $validated, $correlationId) {
                    $product->update([...$validated, 'correlation_id' => $correlationId]);

                    Log::channel('audit')->info('Flower product updated', [
                        'product_id' => $product->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $product;
                });

                return response()->json([
                    'success' => true,
                    'data' => $product,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        public function destroy(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

            try {
                $product = FlowerProduct::query()->findOrFail($id);

                $this->authorize('delete', $product);

                DB::transaction(function () use ($product, $correlationId) {
                    $product->delete();

                    Log::channel('audit')->info('Flower product deleted', [
                        'product_id' => $product->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Product deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
            }
        }
}
