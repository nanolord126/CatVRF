<?php declare(strict_types=1);

namespace App\Domains\Archived\VeganProducts\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeganProductCreatedEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        /**


         * Create a new event instance.


         */


        public function __construct(


            public readonly VeganProduct $product,


            public readonly int $userId,


            public readonly string $correlationId,


            public readonly array $meta = [],


        ) {}


    }


    /**


     * VeganStockAlertEvent - Triggered when stock falls below threshold.


     */


    class VeganStockAlertEvent


    {


        use Dispatchable, SerializesModels;


        public function __construct(


            public readonly VeganProduct $product,


            public readonly int $currentStock,


            public readonly string $correlationId,


        ) {}


    }


    /**


     * VeganSubscriptionRenewedEvent - Triggered after a subscription box is processed.


     */


    class VeganSubscriptionRenewedEvent


    {


        use Dispatchable, SerializesModels;


        public function __construct(


            public readonly int $subscriptionId,


            public readonly int $boxId,


            public readonly string $correlationId,


        ) {}
}
