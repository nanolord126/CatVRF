<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\B2BFlowerOrder;
use App\Domains\Flowers\Models\B2BFlowerStorefront;
use App\Domains\Flowers\Models\FlowerProduct;
use App\Domains\Flowers\Services\B2BFlowerOrderService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class B2BFlowerController extends Controller
{
    public function __construct(
        private B2BFlowerOrderService $b2bOrderService,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $validated = $request->validate([
                'company_inn' => 'required|string|unique:b2b_flower_storefronts',
                'company_name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_phone' => 'required|string',
                'contact_email' => 'required|email',
                'shop_id' => 'required|integer|exists:flower_shops,id',
            ]);

            $tenantId = $request->user()?->tenant_id ?? 0;

            $storefront = $this->db->transaction(function () use ($validated, $correlationId, $tenantId) {
                $storefront = B2BFlowerStorefront::query()->create([
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                    ...$validated,
                ]);

                $this->logger->info('B2B flower storefront created', [
                    'storefront_id' => $storefront->id,
                    'company_inn' => $validated['company_inn'],
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $storefront,
                'message' => 'Registration submitted. Awaiting verification.',
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('B2B registration failed', [
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

    public function profile(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->firstOrFail();

            return new JsonResponse([
                'success' => true,
                'data' => $storefront,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Storefront not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $validated = $request->validate([
                'contact_person' => 'string|max:255',
                'contact_phone' => 'string',
                'contact_email' => 'email',
            ]);

            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->firstOrFail();

            $storefront = $this->db->transaction(function () use ($storefront, $validated, $correlationId) {
                $storefront->update([...$validated, 'correlation_id' => $correlationId]);

                $this->logger->info('B2B storefront updated', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $storefront,
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

    public function products(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->where('is_active', true)
                ->firstOrFail();

            $products = $storefront->shop->products()
                ->where('is_available', true)
                ->paginate(15);

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

    public function productDetail(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $product = FlowerProduct::query()->findOrFail($id);

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

    public function productInquiry(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'message' => 'nullable|string',
            ]);

            $this->logger->info('B2B product inquiry', [
                'product_id' => $id,
                'quantity' => $validated['quantity'],
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Inquiry sent',
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

    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->where('is_active', true)
                ->firstOrFail();

            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'delivery_address' => 'required|string',
                'delivery_date' => 'required|date|after:today',
            ]);

            $tenantId = $request->user()?->tenant_id ?? 0;

            $order = $this->b2bOrderService->createB2BOrder(
                tenantId: $tenantId,
                storefrontId: $storefront->id,
                items: $validated['items'],
                deliveryData: $validated,
                correlationId: $correlationId,
            );

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('B2B order creation failed', [
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

    public function listOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->paginate(15);

            return new JsonResponse([
                'success' => true,
                'data' => $orders,
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

    public function orderDetail(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = B2BFlowerOrder::query()
                ->where('id', $id)
                ->firstOrFail();

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function updateOrder(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            if ($order->status !== 'draft') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cannot update this order',
                    'correlation_id' => $correlationId,
                ], 422);
            }

            $validated = $request->validate([
                'delivery_address' => 'string',
                'delivery_date' => 'date|after:today',
            ]);

            $order = $this->db->transaction(function () use ($order, $validated, $correlationId) {
                $order->update([...$validated, 'correlation_id' => $correlationId]);

                $this->logger->info('B2B order updated', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $order,
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

    public function submitOrder(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            $order = $this->db->transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'submitted']);

                $this->logger->info('B2B order submitted', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $order,
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

    public function cancelOrder(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            if (!in_array($order->status, ['draft', 'submitted'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cannot cancel this order',
                    'correlation_id' => $correlationId,
                ], 422);
            }

            $order = $this->db->transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'cancelled']);

                $this->logger->info('B2B order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $order,
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

    public function orderInvoice(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'company_inn' => $order->storefront->company_inn,
                    'subtotal' => $order->subtotal,
                    'discount' => $order->bulk_discount,
                    'commission' => $order->commission_amount,
                    'total' => $order->total_amount,
                    'delivery_date' => $order->delivery_date,
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invoice not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function ordersAnalytics(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->get();

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'total_orders' => $orders->count(),
                    'pending' => $orders->where('status', 'draft')->count(),
                    'confirmed' => $orders->where('status', 'confirmed')->count(),
                    'delivered' => $orders->where('status', 'delivered')->count(),
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

    public function spendingAnalytics(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', $request->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->where('payment_status', 'paid')
                ->get();

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'total_spent' => $orders->sum('total_amount'),
                    'total_orders' => $orders->count(),
                    'average_order' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
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

    public function adminStorefronts(): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefronts = B2BFlowerStorefront::query()->paginate(20);

            return new JsonResponse([
                'success' => true,
                'data' => $storefronts,
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

    public function adminVerifyStorefront(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $storefront = B2BFlowerStorefront::query()->findOrFail($id);

            $storefront = $this->db->transaction(function () use ($storefront, $correlationId) {
                $storefront->update(['is_verified' => true]);

                $this->logger->info('B2B storefront verified', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $storefront,
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

    public function adminDeleteStorefront(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $this->db->transaction(function () use ($id, $correlationId) {
                $storefront = B2BFlowerStorefront::query()->findOrFail($id);
                $storefront->delete();

                $this->logger->info('B2B storefront deleted', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return new JsonResponse([
                'success' => true,
                'message' => 'Storefront deleted',
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
