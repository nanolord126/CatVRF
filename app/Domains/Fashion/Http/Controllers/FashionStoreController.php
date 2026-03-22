<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionStore;
use App\Domains\Fashion\Services\OrderService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionStoreController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $stores = FashionStore::where('is_verified', true)
                ->where('is_active', true)
                ->with('products')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $stores, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to fetch stores', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $store = FashionStore::with('products', 'orders')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $store, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Store not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function products(int $id): JsonResponse
    {
        try {
            $store = FashionStore::findOrFail($id);
            $products = $store->products()->paginate(20);
            return response()->json(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function reviews(int $id): JsonResponse
    {
        try {
            $store = FashionStore::findOrFail($id);
            $reviews = $store->products()->with('reviews')->get()->flatMap->reviews->paginate(20);
            return response()->json(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            DB::transaction(function () use ($correlationId) {
                FashionStore::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'owner_id' => auth()->id(),
                    'name' => request('name'),
                    'description' => request('description'),
                    'categories' => collect(request('categories', [])),
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Fashion store created', [
                    'owner_id' => auth()->id(),
                    'name' => request('name'),
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function myStore(): JsonResponse
    {
        try {
            $store = FashionStore::where('owner_id', auth()->id())->firstOrFail();
            return response()->json(['success' => true, 'data' => $store, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Store not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $store = FashionStore::findOrFail($id);

            DB::transaction(function () use ($store, $correlationId) {
                $store->update([...request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                Log::channel('audit')->info('Fashion store updated', ['store_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $store, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $store = FashionStore::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($store, $correlationId) {
                $store->delete();
                Log::channel('audit')->info('Fashion store deleted', ['store_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function verify(int $id): JsonResponse
    {
        try {
            $store = FashionStore::findOrFail($id);
            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($store, $correlationId) {
                $store->update(['is_verified' => true, 'correlation_id' => $correlationId]);
                Log::channel('audit')->info('Fashion store verified', ['store_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $store, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $stores = FashionStore::with('products')->paginate(50);
            return response()->json(['success' => true, 'data' => $stores, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $totalStores = FashionStore::count();
            $activeStores = FashionStore::where('is_active', true)->count();
            $verifiedStores = FashionStore::where('is_verified', true)->count();
            $totalProducts = \App\Domains\Fashion\Models\FashionProduct::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_stores' => $totalStores,
                    'active_stores' => $activeStores,
                    'verified_stores' => $verifiedStores,
                    'total_products' => $totalProducts,
                ],
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
