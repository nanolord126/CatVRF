<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrBookingListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct() {}


        /**


         * Создание записи в аудит-логе при новом бронировании


         */


        public function onCreated(StrBookingCreated $event): void


        {


            Log::channel('audit')->info('ShortTermRental: New Booking Event Recieved', [


                'booking_id' => $event->booking->id,


                'user_id' => $event->booking->user_id,


                'correlation_id' => $event->correlationId,


            ]);


            // Здесь может быть вызов сервиса нотификаций или планирование тасок


        }


        /**


         * Действия при завершении проживания


         */


        public function onCompleted(StrBookingCompleted $event): void


        {


            Log::channel('audit')->info('ShortTermRental: Booking Completed Event Recieved', [


                'booking_id' => $event->booking->id,


                'correlation_id' => $event->correlationId,


            ]);


            // Запуск процесса автоматического возврата залога через 24 часа (если нет споров)


            // \App\Domains\Archived\ShortTermRentals\Jobs\AutoReleaseDepositJob::dispatch($event->booking->id)->delay(now()->addDay());


        }
}
