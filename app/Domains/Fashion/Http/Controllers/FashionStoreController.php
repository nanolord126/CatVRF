<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionStoreController extends Controller
{

    public function __construct(private readonly OrderService $orderService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $stores = FashionStore::where('is_verified', true)
                    ->where('is_active', true)
                    ->with('products')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $stores, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch stores', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $store = FashionStore::with('products', 'orders')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $store, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Store not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function products(int $id): JsonResponse
        {
            try {
                $store = FashionStore::findOrFail($id);
                $products = $store->products()->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $products, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function reviews(int $id): JsonResponse
        {
            try {
                $store = FashionStore::findOrFail($id);
                $reviews = $store->products()->with('reviews')->get()->flatMap->reviews->paginate(20);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $reviews, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->db->transaction(function () use ($correlationId) {
                    FashionStore::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant()->id,
                        'owner_id' => $request->user()?->id,
                        'name' => $request->input('name'),
                        'description' => $request->input('description'),
                        'categories' => collect($request->input('categories', [])),
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Fashion store created', [
                        'owner_id' => $request->user()?->id,
                        'name' => $request->input('name'),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function myStore(): JsonResponse
        {
            try {
                $store = FashionStore::where('owner_id', $request->user()?->id)->firstOrFail();
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $store, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Store not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $store = FashionStore::findOrFail($id);

                $this->db->transaction(function () use ($store, $correlationId) {
                    $store->update([...$request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                    $this->logger->info('Fashion store updated', ['store_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $store, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $store = FashionStore::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($store, $correlationId) {
                    $store->delete();
                    $this->logger->info('Fashion store deleted', ['store_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function verify(int $id): JsonResponse
        {
            try {
                $store = FashionStore::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($store, $correlationId) {
                    $store->update(['is_verified' => true, 'correlation_id' => $correlationId]);
                    $this->logger->info('Fashion store verified', ['store_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $store, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $stores = FashionStore::with('products')->paginate(50);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $stores, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $totalStores = FashionStore::count();
                $activeStores = FashionStore::where('is_active', true)->count();
                $verifiedStores = FashionStore::where('is_verified', true)->count();
                $totalProducts = \App\Domains\Fashion\Models\FashionProduct::count();

                return new \Illuminate\Http\JsonResponse([
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
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
