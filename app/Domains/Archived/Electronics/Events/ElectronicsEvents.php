<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicsProductCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        /**


         * Create a new event instance.


         */


        public function __construct(


            public readonly ElectronicsProduct $product,


            public readonly string $correlationId,


        ) {


            Log::channel('audit')->info('LAYER-7: ElectronicsProductCreated EVENT', [


                'sku' => $product->sku,


                'name' => $product->name,


                'correlation_id' => $correlationId,


            ]);


        }


    }


    /**


     * ElectronicsOrderProcessed - Event triggered after a successful gadget sale and stock lock.


     */


    final class ElectronicsOrderProcessed


    {


        use Dispatchable;


        public function __construct(


            public readonly int $orderId,


            public readonly int $productId,


            public readonly int $quantity,


            public readonly string $correlationId,


        ) {


            Log::channel('audit')->info('LAYER-7: ElectronicsOrderProcessed EVENT', [


                'order_id' => $orderId,


                'product_id' => $productId,


                'qty' => $quantity,


                'correlation_id' => $correlationId,


            ]);


        }
}
