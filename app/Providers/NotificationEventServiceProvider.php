<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NotificationEventServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * События и их listener'ы
         */
        protected $listen = [
            // Payment events
            PaymentInitiatedEvent::class => [
                PaymentEventListener::class . '@handlePaymentInitiated',
            ],
            PaymentAuthorizedEvent::class => [
                PaymentEventListener::class . '@handlePaymentAuthorized',
            ],
            PaymentCapturedEvent::class => [
                PaymentEventListener::class . '@handlePaymentCaptured',
            ],
            PaymentFailedEvent::class => [
                PaymentEventListener::class . '@handlePaymentFailed',
            ],
            PaymentRefundedEvent::class => [
                PaymentEventListener::class . '@handlePaymentRefunded',
            ],

            // Beauty events (по мере реализации)
            // 'App\Events\Beauty\AppointmentConfirmedEvent' => [
            //     'App\Listeners\BeautyEventListener@handleAppointmentConfirmed',
            // ],

            // Food events (по мере реализации)
            // 'App\Events\Food\OrderConfirmedEvent' => [
            //     'App\Listeners\FoodEventListener@handleOrderConfirmed',
            // ],
        ];

        /**
         * Subscriber'ы которые слушают множество событий
         */
        protected $subscribe = [
            // App\Listeners\UserEventsSubscriber::class,
        ];

        public function boot(): void
        {
            parent::boot();

            // Можно добавить динамическую регистрацию если нужна
        }
}
