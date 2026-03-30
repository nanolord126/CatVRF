<?php declare(strict_types=1);

namespace App\Domains\Archived\SportsNutrition\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VapeServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**


         * Регистрация тяжелых инициализаций.


         */


        public function boot(): void


        {


            // 1. Привязка событий к слушателям (Trigger "Честный ЗНАК")


            Event::listen(


                VapeOrderPaidEvent::class,


                TriggerVapeMarkingRegistration::class,


            );


            // 2. Логирование инициализации домена


            \Illuminate\Support\Facades\Log::channel('audit')->info('Vape Domain ServiceProvider booted', [


                'tenant_id' => tenant('id') ?? 'system',


            ]);


        }


        /**


         * Регистрация легких привязок.


         */


        public function register(): void


        {


            // Регистрация контроллеров/сервисов (если не через AutoDiscovery)


        }
}
