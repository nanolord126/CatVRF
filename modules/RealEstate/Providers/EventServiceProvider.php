<?php declare(strict_types=1);

namespace Modules\RealEstate\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\RealEstate\Events\BookingConfirmed;
use Modules\RealEstate\Events\BookingCreated;
use Modules\RealEstate\Events\DealCompleted;
use Modules\RealEstate\Listeners\SendBookingConfirmationNotification;
use Modules\RealEstate\Jobs\ProcessBookingExpirationJob;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BookingCreated::class => [
        ],
        BookingConfirmed::class => [
            SendBookingConfirmationNotification::class,
        ],
        DealCompleted::class => [
            UpdatePropertyStatusOnDealComplete::class,
        ],
    ];

    public function boot(): void
    {
    }
}
