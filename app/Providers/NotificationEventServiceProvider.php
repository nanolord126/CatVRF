<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\PaymentInitiatedEvent;
use App\Events\PaymentAuthorizedEvent;
use App\Events\PaymentCapturedEvent;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentRefundedEvent;
use App\Listeners\PaymentEventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * EventServiceProvider - регистрирует все события и listener'ы
 * 
 * Структура:
 * $this->event->class => [Listener1::class, Listener2::class]
 */
final class NotificationEventServiceProvider extends ServiceProvider
{
    /**
     * События и их listener'ы
     */
    protected $listen = [
        // Payment events
        PaymentInitiated$this->event->class => [
            PaymentEventListener::class . '@handlePaymentInitiated',
        ],
        PaymentAuthorized$this->event->class => [
            PaymentEventListener::class . '@handlePaymentAuthorized',
        ],
        PaymentCaptured$this->event->class => [
            PaymentEventListener::class . '@handlePaymentCaptured',
        ],
        PaymentFailed$this->event->class => [
            PaymentEventListener::class . '@handlePaymentFailed',
        ],
        PaymentRefunded$this->event->class => [
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
