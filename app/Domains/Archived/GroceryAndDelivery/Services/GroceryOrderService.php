<?php declare(strict_types=1);

namespace App\Domains\Archived\GroceryAndDelivery\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryOrderService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    GroceryOrder, GroceryProduct, DeliverySlot};


    use App\Services\{FraudControlService, InventoryManagementService, WalletService};


    use Illuminate\Support\Facades\DB;


    use Illuminate\Support\Facades\Log;


    use Illuminate\Database\Eloquent\Collection;


    final readonly class GroceryOrderService


    {


        public function __construct(


            private FraudControlService $fraudControl,


            private InventoryManagementService $inventory,


            private WalletService $wallet,


        ) {}


        /**


         * Создать заказ с фруд-проверкой и холдом товаров


         */


        public function createOrder(


            int $userId,


            int $storeId,


            int $deliverySlotId,


            array $items,


            float $lat,


            float $lon,


            string $correlationId,


        ): GroceryOrder {


            // Проверка фрода перед созданием заказа


            $fraudScore = $this->fraudControl->checkOrder(


                userId: $userId,


                orderType: 'grocery_order',


                totalAmount: array_sum(array_map(fn($i) => $i['quantity'] * $i['price'], $items)),


                correlationId: $correlationId,


            );


            if ($fraudScore > 0.85) {


                throw new \Exception("Order blocked due to fraud detection (score: $fraudScore)");


            }


            return DB::transaction(function () use ($userId, $storeId, $deliverySlotId, $items, $lat, $lon, $correlationId, $fraudScore) {


                $totalPrice = 0;


                $commissionAmount = 0;


                // Холдировать товары и рассчитать цену


                foreach ($items as $item) {


                    $product = GroceryProduct::findOrFail($item['product_id']);


                    $itemTotal = $item['quantity'] * $product->price;


                    $totalPrice += $itemTotal;


                    // Холд товара


                    $this->inventory->reserveStock(


                        itemId: $item['product_id'],


                        quantity: $item['quantity'],


                        sourceType: 'grocery_order',


                        sourceId: $storeId,


                    );


                }


                // Рассчитать комиссию


                $storeCommission = DB::table('grocery_stores')->where('id', $storeId)->value('commission_percent') ?? 14;


                $commissionAmount = (int)($totalPrice * $storeCommission / 100);


                // Создать заказ


                $order = GroceryOrder::create([


                    'uuid' => \Illuminate\Support\Str::uuid(),


                    'tenant_id' => tenant()->id,


                    'user_id' => $userId,


                    'store_id' => $storeId,


                    'delivery_slot_id' => $deliverySlotId,


                    'status' => 'pending',


                    'total_price' => $totalPrice,


                    'delivery_price' => 200, // 200 рублей минимум


                    'commission_amount' => $commissionAmount,


                    'delivery_address' => 'TBD',


                    'lat' => $lat,


                    'lon' => $lon,


                    'placed_at' => now(),


                    'correlation_id' => $correlationId,


                ]);


                // Создать order items


                foreach ($items as $item) {


                    $product = GroceryProduct::findOrFail($item['product_id']);


                    $order->orderItems()->create([


                        'product_id' => $item['product_id'],


                        'quantity' => $item['quantity'],


                        'price_per_unit' => $product->price,


                        'total_price' => $item['quantity'] * $product->price,


                        'correlation_id' => $correlationId,


                    ]);


                }


                // Логирование


                Log::channel('audit')->info('Grocery order created', [


                    'order_id' => $order->id,


                    'user_id' => $userId,


                    'total_price' => $totalPrice,


                    'fraud_score' => $fraudScore,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


        /**


         * Подтвердить заказ (переход от pending к confirmed)


         */


        public function confirmOrder(GroceryOrder $order, string $correlationId): GroceryOrder


        {


            return DB::transaction(function () use ($order, $correlationId) {


                $order->update([


                    'status' => 'confirmed',


                    'correlation_id' => $correlationId,


                ]);


                Log::channel('audit')->info('Grocery order confirmed', [


                    'order_id' => $order->id,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


        /**


         * Завершить заказ и списать товары


         */


        public function completeOrder(GroceryOrder $order, string $correlationId): GroceryOrder


        {


            return DB::transaction(function () use ($order, $correlationId) {


                // Списать товары со склада


                foreach ($order->orderItems as $item) {


                    $this->inventory->deductStock(


                        itemId: $item->product_id,


                        quantity: $item->quantity,


                        reason: 'delivery_completed',


                        sourceType: 'grocery_order',


                        sourceId: $order->id,


                    );


                }


                // Перевести средства бизнесу (минус комиссия платформы)


                $payoutAmount = $order->total_price - $order->commission_amount;


                $this->wallet->credit(


                    tenantId: $order->store()->first()->tenant_id,


                    amount: $payoutAmount,


                    reason: 'order_payout',


                    correlationId: $correlationId,


                );


                $order->update([


                    'status' => 'delivered',


                    'delivered_at' => now(),


                    'correlation_id' => $correlationId,


                ]);


                Log::channel('audit')->info('Grocery order delivered', [


                    'order_id' => $order->id,


                    'payout_amount' => $payoutAmount,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


        /**


         * Отменить заказ и освободить холд товаров


         */


        public function cancelOrder(GroceryOrder $order, string $reason, string $correlationId): GroceryOrder


        {


            return DB::transaction(function () use ($order, $reason, $correlationId) {


                // Освободить холдованные товары


                foreach ($order->orderItems as $item) {


                    $this->inventory->releaseStock(


                        itemId: $item->product_id,


                        quantity: $item->quantity,


                        sourceType: 'grocery_order',


                        sourceId: $order->id,


                    );


                }


                $order->update([


                    'status' => 'cancelled',


                    'correlation_id' => $correlationId,


                ]);


                Log::channel('audit')->info('Grocery order cancelled', [


                    'order_id' => $order->id,


                    'reason' => $reason,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


    }


    final readonly class DeliverySlotManagementService


    {


        /**


         * Получить доступные слоты доставки


         */


        public function getAvailableSlots(int $storeId, \Carbon\CarbonInterface $date): Collection


        {


            return DeliverySlot::where('store_id', $storeId)


                ->whereDate('start_time', $date)


                ->where('is_available', true)


                ->where('current_orders', '<', DB::raw('max_orders'))


                ->get();


        }


        /**


         * Обновить surge-коэффициент на основе спроса


         */


        public function updateSurgeMultiplier(int $slotId): void


        {


            $slot = DeliverySlot::findOrFail($slotId);


            $occupancyRate = $slot->current_orders / $slot->max_orders;


            $multiplier = match (true) {


                $occupancyRate > 0.9 => 1.5,


                $occupancyRate > 0.7 => 1.3,


                $occupancyRate > 0.5 => 1.15,


                default => 1.0,


            };


            $slot->update(['surge_multiplier' => $multiplier]);


            Log::channel('audit')->info('Slot surge updated', [


                'slot_id' => $slotId,


                'occupancy' => $occupancyRate,


                'multiplier' => $multiplier,


            ]);


        }


    }


    final readonly class FastDeliveryService


    {


        /**


         * Найти оптимального курьера для доставки


         */


        public function assignDeliveryPartner(


            GroceryOrder $order,


            string $correlationId,


        ): \App\Domains\Archived\GroceryAndDelivery\Models\DeliveryPartner {


            $partner = DB::table('delivery_partners')


                ->where('store_id', $order->store_id)


                ->where('status', 'available')


                ->orderByRaw('rating DESC')


                ->first();


            if (!$partner) {


                throw new \Exception('No available delivery partners');


            }


            $order->update(['delivery_partner_id' => $partner->id]);


            Log::channel('audit')->info('Delivery partner assigned', [


                'order_id' => $order->id,


                'partner_id' => $partner->id,


                'correlation_id' => $correlationId,


            ]);


            return \App\Domains\Archived\GroceryAndDelivery\Models\DeliveryPartner::findOrFail($partner->id);


        }
}
