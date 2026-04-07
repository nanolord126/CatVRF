<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerProduct;
use App\Domains\Flowers\Models\FlowerShop;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerProductController extends Controller
{
    public function __construct(
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $products = FlowerProduct::query()
                ->where('is_available', true)
                ->when($request->shop_id, fn ($q) => $q->where('shop_id', $request->shop_id))
                ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->with('shop')
                ->paginate(15);

            $this->logger->info('Flower products listed', [
                'count' => $products->count(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Product listing failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $product = FlowerProduct::query()
                ->where('id', $id)
                ->where('is_available', true)
                ->with('shop')
                ->firstOrFail();

            $this->logger->info('Flower product viewed', [
                'product_id' => $product->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Product not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function shopProducts(int $shopId): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = FlowerShop::query()->findOrFail($shopId);
            $products = $shop->products()->where('is_available', true)->paginate(15);

            return new JsonResponse([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Shop not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $query = $request->get('q', '');
            $products = FlowerProduct::query()
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->where('is_available', true)
                ->limit(20)
                ->get();

            $this->logger->info('Flower products searched', [
                'query' => $query,
                'results' => $products->count(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'product_type' => 'required|in:bouquet,arrangement,basket,single_flower,subscription,gift',
                'price' => 'required|numeric|min:1',
                'stock' => 'required|integer|min:0',
            ]);

            $shop = $request->user()->flowerShop;
            if (!$shop) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Flower shop not found',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $tenantId = $request->user()?->tenant_id ?? 0;

            $product = $this->db->transaction(function () use ($validated, $shop, $correlationId, $tenantId) {
                $product = FlowerProduct::query()->create([
                    'tenant_id' => $tenantId,
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                    ...$validated,
                ]);

                $this->logger->info('Flower product created', [
                    'product_id' => $product->id,
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Product creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $product = FlowerProduct::query()->findOrFail($id);

            $this->authorize('update', $product);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'price' => 'numeric|min:1',
                'stock' => 'integer|min:0',
            ]);

            $product = $this->db->transaction(function () use ($product, $validated, $correlationId) {
                $product->update([...$validated, 'correlation_id' => $correlationId]);

                $this->logger->info('Flower product updated', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);

                return $product;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $product,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            $product = FlowerProduct::query()->findOrFail($id);

            $this->authorize('delete', $product);

            $this->db->transaction(function () use ($product, $correlationId) {
                $product->delete();

                $this->logger->info('Flower product deleted', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return new JsonResponse([
                'success' => true,
                'message' => 'Product deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
