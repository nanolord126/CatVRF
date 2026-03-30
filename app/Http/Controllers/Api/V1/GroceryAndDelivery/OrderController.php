<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\GroceryAndDelivery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    GroceryOrderService, DeliverySlotManagementService};
    use App\Domains\GroceryAndDelivery\Models\{GroceryOrder, DeliverySlot};
    use App\Http\Controllers\ApiController;
    use App\Http\Requests\GroceryAndDelivery\CreateOrderRequest;
    use App\Http\Resources\GroceryOrderResource;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Str;

    final class OrderController extends ApiController
    {
        public function __construct(
            private readonly GroceryOrderService $orderService,
        ) {}

        /**
         * Создать заказ
         * POST /api/v1/grocery/orders
         */
        public function store(CreateOrderRequest $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? (string) Str::uuid();

            try {
                $order = $this->orderService->createOrder(
                    userId: auth()->id(),
                    storeId: $request->get('store_id'),
                    deliverySlotId: $request->get('delivery_slot_id'),
                    items: $request->get('items'),
                    lat: $request->get('lat'),
                    lon: $request->get('lon'),
                    correlationId: $correlationId,
                );

                return response()->json([
                    'success' => true,
                    'data' => new GroceryOrderResource($order),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Order creation failed', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Получить заказ
         * GET /api/v1/grocery/orders/{id}
         */
        public function show(GroceryOrder $order): JsonResponse
        {
            $this->authorize('view', $order);

            return response()->json([
                'success' => true,
                'data' => new GroceryOrderResource($order),
            ]);
        }

        /**
         * Отменить заказ
         * POST /api/v1/grocery/orders/{id}/cancel
         */
        public function cancel(GroceryOrder $order): JsonResponse
        {
            $this->authorize('update', $order);
            $correlationId = (string) Str::uuid();

            try {
                $cancelled = $this->orderService->cancelOrder($order, 'user_requested', $correlationId);

                return response()->json([
                    'success' => true,
                    'data' => new GroceryOrderResource($cancelled),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Order cancellation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Подтвердить заказ (оплата)
         * POST /api/v1/grocery/orders/{id}/confirm
         */
        public function confirm(GroceryOrder $order): JsonResponse
        {
            $this->authorize('update', $order);
            $correlationId = (string) Str::uuid();

            $confirmed = $this->orderService->confirmOrder($order, $correlationId);

            return response()->json([
                'success' => true,
                'data' => new GroceryOrderResource($confirmed),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    final class StoreController extends ApiController
    {
        /**
         * Список магазинов с фильтрацией по гео
         * GET /api/v1/grocery/stores?lat=...&lon=...
         */
        public function index(\Illuminate\Http\Request $request): JsonResponse
        {
            $stores = \App\Domains\GroceryAndDelivery\Models\GroceryStore::query()
                ->where('is_verified', true)
                ->where('is_active', true);

            // B2C/B2B проверка
            if ($request->get('b2b') === true) {
                $stores->where('is_b2b_available', true);
            }

            // Фильтр по гео
            if ($request->has('lat') && $request->has('lon')) {
                $lat = (float) $request->get('lat');
                $lon = (float) $request->get('lon');
                $maxDistance = (float) $request->get('max_distance_km', 10);

                $stores->whereRaw(
                    "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lon)) + sin(radians($lat)) * sin(radians(latitude)))) <= $maxDistance"
                );
            }

            return response()->json([
                'success' => true,
                'data' => $stores->get(),
            ]);
        }

        /**
         * Получить магазин
         * GET /api/v1/grocery/stores/{id}
         */
        public function show(\App\Domains\GroceryAndDelivery\Models\GroceryStore $store): JsonResponse
        {
            return response()->json([
                'success' => true,
                'data' => $store->load('products', 'deliverySlots'),
            ]);
        }
    }

    final class SlotController extends ApiController
    {
        public function __construct(
            private readonly DeliverySlotManagementService $slotService,
        ) {}

        /**
         * Получить доступные слоты доставки
         * GET /api/v1/grocery/slots?store_id=...&date=...
         */
        public function index(\Illuminate\Http\Request $request): JsonResponse
        {
            $storeId = $request->get('store_id');
            $date = $request->get('date') ?? now();

            $slots = $this->slotService->getAvailableSlots($storeId, $date);

            return response()->json([
                'success' => true,
                'data' => $slots,
            ]);
        }
    }

    final class DeliveryController extends ApiController
    {
        /**
         * Отслеживание доставки
         * GET /api/v1/grocery/deliveries/{order_id}/track
         */
        public function track(GroceryOrder $order): JsonResponse
        {
            $this->authorize('view', $order);

            $logs = \Illuminate\Support\Facades\DB::table('delivery_logs')
                ->where('order_id', $order->id)
                ->orderBy('timestamp', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'order_status' => $order->status,
                'delivery_logs' => $logs,
                'current_location' => [
                    'lat' => $order->deliveryPartner?->current_location_lat,
                    'lon' => $order->deliveryPartner?->current_location_lon,
                ],
            ]);
        }
}
