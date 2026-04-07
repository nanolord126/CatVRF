<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class NotificationEventServiceProvider
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Providers
 */
final class NotificationEventServiceProvider extends ServiceProvider
{
    

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
