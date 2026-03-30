<?php declare(strict_types=1);

namespace App\Domains\Archived\SportsNutrition\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeOrderPaidEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        public int $orderId;


        public string $correlationId;


        /**


         * Создание события.


         */


        public function __construct(VapeOrder $order, string $correlationId = null)


        {


            $this->orderId = $order->id;


            $this->correlationId = $correlationId ?? (string) Str::uuid();


            Log::channel('audit')->info('Vape order PAID event fired', [


                'order_id' => $this->orderId,


                'correlation_id' => $this->correlationId,


            ]);


        }
}
