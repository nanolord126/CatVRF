<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerShopController extends Model
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
                $shops = FlowerShop::query()
                    ->where('is_active', true)
                    ->where('is_verified', true)
                    ->when($request->search, fn ($q) => $q->where('shop_name', 'like', "%{$request->search}%"))
                    ->with('products')
                    ->paginate(15);

                return response()->json([
                    'success' => true,
                    'data' => $shops,
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

        public function show(int $id): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shop = FlowerShop::query()
                    ->where('id', $id)
                    ->with(['products' => fn ($q) => $q->where('is_available', true)])
                    ->firstOrFail();

                Log::channel('audit')->info('Flower shop viewed', [
                    'shop_id' => $shop->id,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $shop,
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

        public function myShop(): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shop = auth()->user()->flowerShop;

                if (!$shop) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No flower shop found',
                        'correlation_id' => $correlationId,
                    ], $this->response->HTTP_NOT_FOUND);
                }

                return response()->json([
                    'success' => true,
                    'data' => $shop->load('products', 'orders', 'reviews'),
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
                    'shop_name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'phone' => 'required|string',
                    'address' => 'required|string',
                    'delivery_radius_km' => 'integer|min:1|max:50',
                ]);

                $shop = DB::transaction(function () use ($validated, $correlationId) {
                    $shop = FlowerShop::query()->create([
                        'tenant_id' => filament()->getTenant()->id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                        ...$validated,
                    ]);

                    Log::channel('audit')->info('Flower shop created', [
                        'shop_id' => $shop->id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);

                    return $shop;
                });

                return response()->json([
                    'success' => true,
                    'data' => $shop,
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_CREATED);
            } catch (\Exception $exception) {
                Log::channel('audit')->error('Shop creation failed', [
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
                $shop = FlowerShop::query()->findOrFail($id);

                $this->authorize('update', $shop);

                $validated = $request->validate([
                    'shop_name' => 'string|max:255',
                    'description' => 'nullable|string',
                    'delivery_fee' => 'numeric|min:0',
                    'delivery_radius_km' => 'integer|min:1|max:50',
                ]);

                $shop = DB::transaction(function () use ($shop, $validated, $correlationId) {
                    $shop->update([...$validated, 'correlation_id' => $correlationId]);

                    Log::channel('audit')->info('Flower shop updated', [
                        'shop_id' => $shop->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $shop;
                });

                return response()->json([
                    'success' => true,
                    'data' => $shop,
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

        public function adminList(): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shops = FlowerShop::query()
                    ->with(['user', 'orders'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $shops,
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

        public function adminShow(int $id): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shop = FlowerShop::query()
                    ->where('id', $id)
                    ->with(['user', 'products', 'orders', 'reviews'])
                    ->firstOrFail();

                return response()->json([
                    'success' => true,
                    'data' => $shop,
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

        public function verify(int $id): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $shop = DB::transaction(function () use ($id, $correlationId) {
                    $shop = FlowerShop::query()
                        ->where('id', $id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $shop->update(['is_verified' => true]);

                    Log::channel('audit')->info('Flower shop verified', [
                        'shop_id' => $shop->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $shop;
                });

                return response()->json([
                    'success' => true,
                    'data' => $shop,
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

        public function adminDestroy(int $id): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                DB::transaction(function () use ($id, $correlationId) {
                    $shop = FlowerShop::query()->findOrFail($id);
                    $shop->delete();

                    Log::channel('audit')->info('Flower shop deleted', [
                        'shop_id' => $shop->id,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Shop deleted',
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

        public function adminAnalytics(): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

            try {
                $totalShops = FlowerShop::query()->count();
                $verifiedShops = FlowerShop::query()->where('is_verified', true)->count();
                $totalOrders = \App\Domains\Flowers\Models\FlowerOrder::query()->count();
                $totalRevenue = \App\Domains\Flowers\Models\FlowerOrder::query()
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_shops' => $totalShops,
                        'verified_shops' => $verifiedShops,
                        'total_orders' => $totalOrders,
                        'total_revenue' => $totalRevenue,
                    ],
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

        public function adminEarnings(): JsonResponse
        {
            $correlationId = (string)Str::uuid()->toString();

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

                return response()->json([
                    'success' => true,
                    'data' => $earnings,
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
