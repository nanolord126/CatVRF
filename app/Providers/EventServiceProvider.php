<?php declare(strict_types=1);

namespace App\Providers;

use App\Domains\Auto\Events\RideCreated;
use App\Domains\Auto\Events\RideCompleted;
use App\Domains\Auto\Events\SurgeUpdated;
use App\Domains\Auto\Listeners\NotifyDriverRideCreated;
use App\Domains\Auto\Listeners\ProcessRideCompletedPayout;

use App\Domains\Beauty\Events\AppointmentScheduled;
use App\Domains\Beauty\Events\ConsumableDeducted;
use App\Domains\Beauty\Listeners\SendAppointmentReminder;
use App\Domains\Beauty\Listeners\UpdateConsumableInventory;

use App\Domains\Food\Events\OrderCreated;
use App\Domains\Food\Events\OrderDelivered;
use App\Domains\Food\Listeners\NotifyRestaurantNewOrder;
use App\Domains\Food\Listeners\ProcessOrderDeliveredCommission;

use App\Domains\Hotels\Events\CheckoutCompleted;
use App\Domains\Hotels\Listeners\ScheduleHotelPayout;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Auto Events
        RideCreated::class => [
            NotifyDriverRideCreated::class,
        ],
        RideCompleted::class => [
            ProcessRideCompletedPayout::class,
        ],
        SurgeUpdated::class => [
        ],

        // Beauty Events
        AppointmentScheduled::class => [
            SendAppointmentReminder::class,
        ],
        ConsumableDeducted::class => [
            UpdateConsumableInventory::class,
        ],

        // Food Events
        OrderCreated::class => [
            NotifyRestaurantNewOrder::class,
        ],
        OrderDelivered::class => [
            ProcessOrderDeliveredCommission::class,
        ],

        // Hotels Events
        CheckoutCompleted::class => [
            ScheduleHotelPayout::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
