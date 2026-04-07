<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionRetailShopController extends Controller
{

    public function __construct(private readonly ShopService $shopService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $shops = FashionRetailShop::where('is_active', true)
                    ->paginate(20);

                $correlationId = Str::uuid()->toString();
                $this->logger->info('FashionRetail shops listed', [
                    'count' => $shops->count(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $shops,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail shop listing failed', [
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
                $shop = FashionRetailShop::with('products', 'orders')->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $shop,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Shop not found',
                    'correlation_id' => Str::uuid(),
                ], 404);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $shop = $this->db->transaction(function () use ($correlationId) {
                    return FashionRetailShop::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'business_group_id' => tenant()->business_group_id,
                        'name' => $request->input('name'),
                        'description' => $request->input('description'),
                        'address' => $request->input('address'),
                        'phone' => $request->input('phone'),
                        'email' => $request->input('email'),
                        'website' => $request->input('website'),
                        'owner_id' => $request->user()?->id,
                        'categories' => $request->input('categories', []),
                        'logo_url' => $request->input('logo_url'),
                        'is_verified' => false,
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail shop created', [
                    'shop_id' => $shop->id,
                    'owner_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $shop,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail shop creation failed', [
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
                $shop = FashionRetailShop::findOrFail($id);

                if ($shop->owner_id !== $request->user()?->id) {
                    return new \Illuminate\Http\JsonResponse([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $this->db->transaction(function () use ($shop, $correlationId) {
                    $shop->update([
                        'name' => $request->input('name', $shop->name),
                        'description' => $request->input('description', $shop->description),
                        'phone' => $request->input('phone', $shop->phone),
                        'website' => $request->input('website', $shop->website),
                        'categories' => $request->input('categories', $shop->categories),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('FashionRetail shop updated', [
                    'shop_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $shop,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $correlationId = Str::uuid()->toString();
                $this->logger->error('FashionRetail shop update failed', [
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
