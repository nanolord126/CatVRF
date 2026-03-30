<?php declare(strict_types=1);

namespace App\Domains\Beauty\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyEventServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
