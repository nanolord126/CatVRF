<?php declare(strict_types=1);

namespace App\Domains\Archived\SportsNutrition\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TriggerVapeMarkingRegistration extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;


        /**


         * Создание слушателя.


         */


        public function __construct() {}


        /**


         * Обработка события оплаты.


         */


        public function handle(VapeOrderPaidEvent $event): void


        {


            Log::channel('audit')->info('Vape order paid listener: sending to marking job', [


                'order_id' => $event->orderId,


                'correlation_id' => $event->correlationId,


            ]);


            // Диспетчеризация задачи на регистрацию выбытия


            VapeMarkingRegistrationJob::dispatch(


                orderId: $event->orderId,


                correlationId: $event->correlationId,


            )->onQueue('low_stock'); // Очередь низкого приоритета (ГИС МТ)


        }
}
