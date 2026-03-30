<?php declare(strict_types=1);

namespace App\Domains\Archived\MeatShops\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatShopsService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraudControlService,


        ) {}


        public function createOrder(int $productId, float $weightKg, int $clientId, Carbon $deliveryDate, int $tenantId, string $correlationId): MeatOrder


        {


            return DB::transaction(function () use ($productId, $weightKg, $clientId, $deliveryDate, $tenantId, $correlationId) {


                $this->fraudControlService->check(


                    userId: $clientId,


                    operationType: 'meat_order',


                    amount: 0,


                    correlationId: $correlationId,


                );


                $product = MeatProduct::findOrFail($productId);


                $totalPrice = (int)($product->price * $weightKg);


                $order = MeatOrder::create([


                    'tenant_id' => $tenantId,


                    'uuid' => Str::uuid(),


                    'correlation_id' => $correlationId,


                    'product_id' => $productId,


                    'client_id' => $clientId,


                    'weight_kg' => $weightKg,


                    'unit_price' => $product->price,


                    'total_price' => $totalPrice,


                    'delivery_date' => $deliveryDate,


                    'status' => 'pending',


                    'idempotency_key' => md5("{$clientId}:{$productId}:{$weightKg}:{$deliveryDate}:{$tenantId}"),


                ]);


                MeatOrderCreated::dispatch($order->id, $tenantId, $clientId, $totalPrice, $correlationId);


                Log::channel('audit')->info('Meat order created', [


                    'order_id' => $order->id,


                    'product_id' => $productId,


                    'weight_kg' => $weightKg,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


        public function markDelivered(int $orderId, int $tenantId, string $correlationId): MeatOrder


        {


            $order = MeatOrder::lockForUpdate()


                ->where('id', $orderId)


                ->where('tenant_id', $tenantId)


                ->firstOrFail();


            if (!$order->isPending()) {


                throw new \Exception("Order {$orderId} is not in pending state");


            }


            $order->update(['status' => 'delivered']);


            Log::channel('audit')->info('Meat order delivered', [


                'order_id' => $order->id,


                'correlation_id' => $correlationId,


            ]);


            return $order;


        }


        public function getProductsByAnimalType(string $animalType, int $tenantId)


        {


            return MeatProduct::where('tenant_id', $tenantId)


                ->where('animal_type', $animalType)


                ->get();


        }
}
