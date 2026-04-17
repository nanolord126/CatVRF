<?php declare(strict_types=1);

namespace Modules\RealEstate\Observers;

use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Events\BookingCreated;
use Modules\RealEstate\Events\BookingConfirmed;
use Modules\RealEstate\Events\DealCompleted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

final class PropertyBookingObserver
{
    public function created(PropertyBooking $booking): void
    {
        Log::channel('audit')->info('real_estate.booking.observed.created', [
            'booking_id' => $booking->id,
            'property_id' => $booking->property_id,
            'user_id' => $booking->user_id,
            'correlation_id' => $booking->correlation_id,
        ]);

        event(new BookingCreated($booking, $booking->correlation_id));

        Cache::forget("property_availability:{$booking->property_id}");
    }

    public function updated(PropertyBooking $booking): void
    {
        $oldStatus = $booking->getOriginal('status');
        $newStatus = $booking->status->value;

        if ($oldStatus !== $newStatus) {
            Log::channel('audit')->info('real_estate.booking.observed.status_changed', [
                'booking_id' => $booking->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $booking->correlation_id,
            ]);

            if ($newStatus === BookingStatus::CONFIRMED->value && $oldStatus === BookingStatus::PENDING->value) {
                event(new BookingConfirmed($booking, $booking->correlation_id));
            }

            if ($newStatus === BookingStatus::COMPLETED->value && $oldStatus === BookingStatus::CONFIRMED->value) {
                $property = $booking->property;
                event(new DealCompleted($booking, $property, $booking->correlation_id));
            }
        }

        Cache::forget("property_availability:{$booking->property_id}");
    }

    public function deleted(PropertyBooking $booking): void
    {
        Log::channel('audit')->info('real_estate.booking.observed.deleted', [
            'booking_id' => $booking->id,
            'correlation_id' => $booking->correlation_id,
        ]);

        Cache::forget("property_availability:{$booking->property_id}");
    }

    public function restored(PropertyBooking $booking): void
    {
        Log::channel('audit')->info('real_estate.booking.observed.restored', [
            'booking_id' => $booking->id,
            'correlation_id' => $booking->correlation_id,
        ]);
    }

    public function forceDeleted(PropertyBooking $booking): void
    {
        Log::channel('audit')->info('real_estate.booking.observed.force_deleted', [
            'booking_id' => $booking->id,
            'correlation_id' => $booking->correlation_id,
        ]);
    }
}
