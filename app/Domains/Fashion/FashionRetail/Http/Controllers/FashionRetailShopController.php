<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;

use App\Domains\Fashion\FashionRetail\Models\FashionRetailShop;
use App\Domains\Fashion\FashionRetail\Services\ShopService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionRetailShopController
{
    public function __construct(
        private readonly ShopService $shopService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $shops = FashionRetailShop::where('is_active', true)
                ->paginate(20);

            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('FashionRetail shops listed', [
                'count' => $shops->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $shops,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail shop listing failed', [
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
            $shop = FashionRetailShop::with('products', 'orders')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $shop,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $shop = DB::transaction(function () use ($correlationId) {
                return FashionRetailShop::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'business_group_id' => filament()->getTenant()->business_group_id,
                    'name' => request('name'),
                    'description' => request('description'),
                    'address' => request('address'),
                    'phone' => request('phone'),
                    'email' => request('email'),
                    'website' => request('website'),
                    'owner_id' => auth()->id(),
                    'categories' => request('categories', []),
                    'logo_url' => request('logo_url'),
                    'is_verified' => false,
                    'is_active' => true,
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail shop created', [
                'shop_id' => $shop->id,
                'owner_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $shop,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail shop creation failed', [
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
            $shop = FashionRetailShop::findOrFail($id);

            if ($shop->owner_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            DB::transaction(function () use ($shop, $correlationId) {
                $shop->update([
                    'name' => request('name', $shop->name),
                    'description' => request('description', $shop->description),
                    'phone' => request('phone', $shop->phone),
                    'website' => request('website', $shop->website),
                    'categories' => request('categories', $shop->categories),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail shop updated', [
                'shop_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $shop,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail shop update failed', [
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
