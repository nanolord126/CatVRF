<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicRentalService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Rental an instrument.
         */
        public function rentalInstrument(int $instrumentId, Carbon $startsAt, Carbon $endsAt, int $userId): MusicBooking
        {
            FraudControlService::check();

            return DB::transaction(function () use ($instrumentId, $startsAt, $endsAt, $userId) {
                $instrument = MusicInstrument::lockForUpdate()->findOrFail($instrumentId);
                $correlationId = (string) Str::uuid();

                if ($instrument->availableStock() <= 0) {
                    throw new \Exception('Insufficient stock for instrument: ' . $instrument->name);
                }

                $instrument->increment('hold_stock');

                $totalPriceCents = (int) ($instrument->rental_price_cents * $startsAt->diffInDays($endsAt));

                $booking = MusicBooking::create([
                    'user_id' => $userId,
                    'bookable_id' => $instrumentId,
                    'bookable_type' => MusicInstrument::class,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'total_price_cents' => $totalPriceCents,
                    'status' => 'confirmed',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Music instrument rented', [
                    'booking_id' => $booking->id,
                    'instrument_id' => $instrumentId,
                    'total_price' => $totalPriceCents,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });
        }

        /**
         * Release rental instrument (complete/return).
         */
        public function completeRental(int $bookingId): void
        {
            FraudControlService::check();

            DB::transaction(function () use ($bookingId) {
                $booking = MusicBooking::where('bookable_type', MusicInstrument::class)
                    ->where('status', 'confirmed')
                    ->findOrFail($bookingId);

                $instrument = MusicInstrument::where('id', $booking->bookable_id)->lockForUpdate()->firstOrFail();

                if ($instrument->hold_stock <= 0) {
                    throw new \Exception('System error: hold stock cannot be less than 0.');
                }

                $instrument->decrement('hold_stock');
                $booking->update(['status' => 'completed']);

                Log::channel('audit')->info('Music instrument rental completed', [
                    'booking_id' => $bookingId,
                    'instrument_id' => $instrument->id,
                    'correlation_id' => $booking->correlation_id,
                ]);
            });
        }

        /**
         * Get all currently rented instruments.
         */
        public function getActiveRentals(): Collection
        {
            return MusicBooking::where('bookable_type', MusicInstrument::class)
                ->where('status', 'confirmed')
                ->get();
        }
}
