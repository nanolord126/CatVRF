<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Models\FlowerShop;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerShopController extends Controller
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
            $shops = FlowerShop::query()
                ->where('is_active', true)
                ->where('is_verified', true)
                ->when($request->search, fn ($q) => $q->where('shop_name', 'like', "%{$request->search}%"))
                ->with('products')
                ->paginate(15);

            return new JsonResponse([
                'success' => true,
                'data' => $shops,
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

    public function show(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = FlowerShop::query()
                ->where('id', $id)
                ->with(['products' => fn ($q) => $q->where('is_available', true)])
                ->firstOrFail();

            $this->logger->info('Flower shop viewed', [
                'shop_id' => $shop->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $shop,
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

    public function myShop(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = $request->user()->flowerShop;

            if (!$shop) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No flower shop found',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $shop->load('products', 'orders', 'reviews'),
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
                'shop_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'phone' => 'required|string',
                'address' => 'required|string',
                'delivery_radius_km' => 'integer|min:1|max:50',
            ]);

            $tenantId = $request->user()?->tenant_id ?? 0;
            $userId = $request->user()?->id;

            $shop = $this->db->transaction(function () use ($validated, $correlationId, $tenantId, $userId) {
                $shop = FlowerShop::query()->create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                    ...$validated,
                ]);

                $this->logger->info('Flower shop created', [
                    'shop_id' => $shop->id,
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]);

                return $shop;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $shop,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Shop creation failed', [
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
            $shop = FlowerShop::query()->findOrFail($id);

            $this->authorize('update', $shop);

            $validated = $request->validate([
                'shop_name' => 'string|max:255',
                'description' => 'nullable|string',
                'delivery_fee' => 'numeric|min:0',
                'delivery_radius_km' => 'integer|min:1|max:50',
            ]);

            $shop = $this->db->transaction(function () use ($shop, $validated, $correlationId) {
                $shop->update([...$validated, 'correlation_id' => $correlationId]);

                $this->logger->info('Flower shop updated', [
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                ]);

                return $shop;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $shop,
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

    public function adminList(): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shops = FlowerShop::query()
                ->with(['user', 'orders'])
                ->paginate(20);

            return new JsonResponse([
                'success' => true,
                'data' => $shops,
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

    public function adminShow(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = FlowerShop::query()
                ->where('id', $id)
                ->with(['user', 'products', 'orders', 'reviews'])
                ->firstOrFail();

            return new JsonResponse([
                'success' => true,
                'data' => $shop,
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

    public function verify(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = $this->db->transaction(function () use ($id, $correlationId) {
                $shop = FlowerShop::query()
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $shop->update(['is_verified' => true]);

                $this->logger->info('Flower shop verified', [
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                ]);

                return $shop;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $shop,
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

    public function adminDestroy(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $this->db->transaction(function () use ($id, $correlationId) {
                $shop = FlowerShop::query()->findOrFail($id);
                $shop->delete();

                $this->logger->info('Flower shop deleted', [
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return new JsonResponse([
                'success' => true,
                'message' => 'Shop deleted',
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

    public function adminAnalytics(): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $totalShops = FlowerShop::query()->count();
            $verifiedShops = FlowerShop::query()->where('is_verified', true)->count();
            $totalOrders = FlowerOrder::query()->count();
            $totalRevenue = FlowerOrder::query()
                ->where('payment_status', 'paid')
                ->sum('total_amount');

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'total_shops' => $totalShops,
                    'verified_shops' => $verifiedShops,
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                ],
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

    public function adminEarnings(): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $earnings = FlowerShop::query()
                ->with('orders')
                ->get()
                ->map(fn ($shop) => [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->shop_name,
                    'orders_count' => $shop->orders->count(),
                    'total_earned' => $shop->orders->sum('total_amount'),
                    'commission' => $shop->orders->sum('commission_amount'),
                ]);

            return new JsonResponse([
                'success' => true,
                'data' => $earnings,
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
