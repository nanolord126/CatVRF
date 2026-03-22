<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\B2BFlowerOrder;
use App\Domains\Flowers\Models\B2BFlowerStorefront;
use App\Domains\Flowers\Services\B2BFlowerOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class B2BFlowerController
{
    public function __construct(
        private readonly B2BFlowerOrderService $b2bOrderService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'company_inn' => 'required|string|unique:b2b_flower_storefronts',
                'company_name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_phone' => 'required|string',
                'contact_email' => 'required|email',
                'shop_id' => 'required|integer|exists:flower_shops,id',
            ]);

            $storefront = DB::transaction(function () use ($validated, $correlationId) {
                $storefront = B2BFlowerStorefront::query()->create([
                    'tenant_id' => filament()->getTenant()->id,
                    'correlation_id' => $correlationId,
                    ...$validated,
                ]);

                Log::channel('audit')->info('B2B flower storefront created', [
                    'storefront_id' => $storefront->id,
                    'company_inn' => $validated['company_inn'],
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return response()->json([
                'success' => true,
                'data' => $storefront,
                'message' => 'Registration submitted. Awaiting verification.',
                'correlation_id' => $correlationId,
            ], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            Log::channel('audit')->error('B2B registration failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function profile(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $storefront,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Storefront not found',
                'correlation_id' => $correlationId,
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'contact_person' => 'string|max:255',
                'contact_phone' => 'string',
                'contact_email' => 'email',
            ]);

            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->firstOrFail();

            $storefront = DB::transaction(function () use ($storefront, $validated, $correlationId) {
                $storefront->update([...$validated, 'correlation_id' => $correlationId]);

                Log::channel('audit')->info('B2B storefront updated', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return response()->json([
                'success' => true,
                'data' => $storefront,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function products(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->where('is_active', true)
                ->firstOrFail();

            $products = $storefront->shop->products()
                ->where('is_available', true)
                ->paginate(15);

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
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function productDetail(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $product = \App\Domains\Flowers\Models\FlowerProduct::query()
                ->findOrFail($id);

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
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function productInquiry(int $id, Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
                'message' => 'nullable|string',
            ]);

            Log::channel('audit')->info('B2B product inquiry', [
                'product_id' => $id,
                'quantity' => $validated['quantity'],
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inquiry sent',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->where('is_active', true)
                ->firstOrFail();

            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'delivery_address' => 'required|string',
                'delivery_date' => 'required|date|after:today',
            ]);

            $order = $this->b2bOrderService->createB2BOrder(
                tenantId: filament()->getTenant()->id,
                storefrontId: $storefront->id,
                items: $validated['items'],
                deliveryData: $validated,
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ], Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            Log::channel('audit')->error('B2B order creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function listOrders(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function orderDetail(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = B2BFlowerOrder::query()
                ->where('id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function updateOrder(int $id, Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            if ($order->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update this order',
                    'correlation_id' => $correlationId,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validated = $request->validate([
                'delivery_address' => 'string',
                'delivery_date' => 'date|after:today',
            ]);

            $order = DB::transaction(function () use ($order, $validated, $correlationId) {
                $order->update([...$validated, 'correlation_id' => $correlationId]);

                Log::channel('audit')->info('B2B order updated', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function submitOrder(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            $order = DB::transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'submitted']);

                Log::channel('audit')->info('B2B order submitted', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelOrder(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            if (!in_array($order->status, ['draft', 'submitted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this order',
                    'correlation_id' => $correlationId,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order = DB::transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'cancelled']);

                Log::channel('audit')->info('B2B order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function orderInvoice(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = B2BFlowerOrder::query()->findOrFail($id);

            return response()->json([
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
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'correlation_id' => $correlationId,
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function ordersAnalytics(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_orders' => $orders->count(),
                    'pending' => $orders->where('status', 'draft')->count(),
                    'confirmed' => $orders->where('status', 'confirmed')->count(),
                    'delivered' => $orders->where('status', 'delivered')->count(),
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function spendingAnalytics(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()
                ->where('company_inn', auth()->user()->company_inn)
                ->firstOrFail();

            $orders = B2BFlowerOrder::query()
                ->where('storefront_id', $storefront->id)
                ->where('payment_status', 'paid')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_spent' => $orders->sum('total_amount'),
                    'total_orders' => $orders->count(),
                    'average_order' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function adminStorefronts(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefronts = B2BFlowerStorefront::query()->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $storefronts,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function adminVerifyStorefront(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $storefront = B2BFlowerStorefront::query()->findOrFail($id);

            $storefront = DB::transaction(function () use ($storefront, $correlationId) {
                $storefront->update(['is_verified' => true]);

                Log::channel('audit')->info('B2B storefront verified', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);

                return $storefront;
            });

            return response()->json([
                'success' => true,
                'data' => $storefront,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function adminDeleteStorefront(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            DB::transaction(function () use ($id, $correlationId) {
                $storefront = B2BFlowerStorefront::query()->findOrFail($id);
                $storefront->delete();

                Log::channel('audit')->info('B2B storefront deleted', [
                    'storefront_id' => $storefront->id,
                    'correlation_id' => $correlationId,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Storefront deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
