<?php declare(strict_types=1);

namespace App\Domains\Archived\Furniture\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureDomainService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly FraudControlService $fraudControl


        ) {}


        /**


         * Create or update a furniture product with stock management and validation.


         * Transactional + Audit logged.


         */


        public function saveProduct(FurnitureProductDto $dto): FurnitureProduct


        {


            $correlationId = $dto->correlationId ?? (string) Str::uuid();


            Log::channel('audit')->info('LAYER-3: Saving Furniture Product', [


                'sku' => $dto->sku,


                'correlation_id' => $correlationId,


            ]);


            return DB::transaction(function () use ($dto, $correlationId) {


                $product = FurnitureProduct::updateOrCreate(


                    ['sku' => $dto->sku],


                    [


                        'name' => $dto->name,


                        'furniture_store_id' => $dto->storeId,


                        'furniture_category_id' => $dto->categoryId,


                        'description' => $dto->description,


                        'properties' => $dto->properties,


                        'price_b2c' => $dto->priceB2c,


                        'price_b2b' => $dto->priceB2b,


                        'stock_quantity' => $dto->stock,


                        'is_oversized' => $dto->isOversized,


                        'requires_assembly' => $dto->requiresAssembly,


                        'assembly_cost' => $dto->assemblyCost,


                        'threed_preview_url' => $dto->threeDUrl,


                        'recommended_room_types' => $dto->recommendedRooms,


                        'correlation_id' => $correlationId,


                        'tags' => $dto->tags,


                    ]


                );


                Log::channel('audit')->info('LAYER-3: Product Saved Successfully', [


                    'id' => $product->id,


                    'correlation_id' => $correlationId,


                ]);


                return $product;


            });


        }


        /**


         * Process a custom interior order (typically from AI Constructor).


         * Includes fraud check, transaction, and wallet hold.


         */


        public function createCustomOrder(FurnitureCustomOrderDto $dto): FurnitureCustomOrder


        {


            $correlationId = $dto->correlationId ?? (string) Str::uuid();


            Log::channel('audit')->info('LAYER-3: Initiating Custom Interior Order', [


                'user_id' => $dto->userId,


                'correlation_id' => $correlationId,


            ]);


            // 1. Mandatory Fraud Check


            $this->fraudControl->check('furniture_order_create', [


                'user_id' => $dto->userId,


                'amount' => $dto->totalAmount,


                'correlation_id' => $correlationId


            ]);


            return DB::transaction(function () use ($dto, $correlationId) {


                // 2. Lock involved products to prevent over-sale


                $productIds = $dto->aiSpecification['product_ids'] ?? [];


                if (!empty($productIds)) {


                    $products = FurnitureProduct::whereIn('id', $productIds)


                        ->lockForUpdate()


                        ->get();


                    foreach ($products as $product) {


                        if ($product->stock_quantity <= 0) {


                            throw new Exception("Product #{$product->sku} is out of stock.");


                        }


                        $product->decrement('stock_quantity');


                        $product->increment('hold_stock');


                    }


                }


                // 3. Create the order record


                $order = FurnitureCustomOrder::create([


                    'user_id' => $dto->userId,


                    'room_type_id' => $dto->roomTypeId,


                    'total_amount' => $dto->totalAmount,


                    'ai_specification' => $dto->aiSpecification,


                    'room_photo_analysis' => $dto->photoAnalysis,


                    'include_assembly' => $dto->includeAssembly,


                    'status' => 'pending',


                    'correlation_id' => $correlationId,


                ]);


                Log::channel('audit')->info('LAYER-3: Custom Order Created', [


                    'order_id' => $order->id,


                    'correlation_id' => $correlationId,


                ]);


                return $order;


            });


        }


        /**


         * Calculate final price based on user role (B2C/B2B) and volume.


         */


        public function calculatePricing(FurnitureProduct $product, bool $isB2B, int $quantity = 1): int


        {


            $basePrice = $isB2B ? $product->price_b2b : $product->price_b2c;


            $total = $basePrice * $quantity;


            // Apply volume discount for B2B


            if ($isB2B && $quantity >= 10) {


                $total = (int) ($total * 0.9); // 10% discount


            }


            return $total;


        }
}
