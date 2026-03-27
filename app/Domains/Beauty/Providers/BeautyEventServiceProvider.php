<?php

declare(strict_types=1);


namespace App\Domains\Beauty\Providers;

use App\Domains\Beauty\Events\AppointmentCancelled;
use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Events\AppointmentConfirmed;
use App\Domains\Beauty\Events\ConsumablesDepleted;
use App\Domains\Beauty\Events\MasterRatingUpdated;
use App\Domains\Beauty\Events\ReviewSubmitted;
use App\Domains\Beauty\Events\SalonVerified;
use App\Domains\Beauty\Events\ServiceCreated;
use App\Domains\Beauty\Listeners\HandleAppointmentCancelledListener;
use App\Domains\Beauty\Listeners\HandleAppointmentCompletedListener;
use App\Domains\Beauty\Listeners\HandleAppointmentConfirmedListener;
use App\Domains\Beauty\Listeners\HandleConsumablesDepletedListener;
use App\Domains\Beauty\Listeners\HandleMasterRatingUpdatedListener;
use App\Domains\Beauty\Listeners\HandleReviewSubmittedListener;
use App\Domains\Beauty\Listeners\HandleSalonVerifiedListener;
use App\Domains\Beauty\Listeners\HandleServiceCreatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final /**
 * BeautyEventServiceProvider
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BeautyEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentCompleted::class => [
            HandleAppointmentCompletedListener::class,
        ],
        AppointmentCancelled::class => [
            HandleAppointmentCancelledListener::class,
        ],
        AppointmentConfirmed::class => [
            HandleAppointmentConfirmedListener::class,
        ],
        ConsumablesDepleted::class => [
            HandleConsumablesDepletedListener::class,
        ],
        MasterRatingUpdated::class => [
            HandleMasterRatingUpdatedListener::class,
        ],
        ReviewSubmitted::class => [
            HandleReviewSubmittedListener::class,
        ],
        SalonVerified::class => [
            HandleSalonVerifiedListener::class,
        ],
        ServiceCreated::class => [
            HandleServiceCreatedListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
