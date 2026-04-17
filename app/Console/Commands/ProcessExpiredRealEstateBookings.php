<?php declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Enums\BookingStatus;
use Modules\RealEstate\Jobs\ProcessBookingExpirationJob;
use Illuminate\Support\Facades\Log;

final class ProcessExpiredRealEstateBookings extends Command
{
    protected $signature = 'real-estate:process-expired-bookings';
    protected $description = 'Process expired pending bookings and cancel them';

    public function handle(): int
    {
        $this->info('Processing expired real estate bookings...');

        $expiredBookings = PropertyBooking::where('status', BookingStatus::PENDING)
            ->where('hold_until', '<', now())
            ->get();

        $count = $expiredBookings->count();

        if ($count === 0) {
            $this->info('No expired bookings found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} expired bookings.");

        foreach ($expiredBookings as $booking) {
            ProcessBookingExpirationJob::dispatch($booking->id);

            $this->line("Dispatched expiration job for booking {$booking->id}");
        }

        Log::channel('audit')->info('real_estate.expired_bookings.processed', [
            'count' => $count,
            'booking_ids' => $expiredBookings->pluck('id')->toArray(),
        ]);

        $this->info("Processed {$count} expired bookings successfully.");

        return Command::SUCCESS;
    }
}
