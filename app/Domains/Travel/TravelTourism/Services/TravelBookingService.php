<?php declare(strict_types=1);

namespace App\Domains\Travel\TravelTourism\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelBookingService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,
            private readonly WalletService $walletService,
        ) {}

        public function createBooking(array $data): TravelBooking
        {


            Log::channel('audit')->info('TravelBookingService: Creating travel booking', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tour_id' => $data['tour_id'],
                'tenant_id' => filament()->getTenant()->id,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(fn () => TravelBooking::create([
                'uuid' => Str::uuid(),
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tenant_id' => filament()->getTenant()->id,
                'tour_id' => $data['tour_id'],
                'traveler_name' => $data['traveler_name'],
                'traveler_email' => $data['traveler_email'],
                'traveler_phone' => $data['traveler_phone'],
                'participants' => $data['participants'] ?? 1,
                'total_price' => $data['total_price'],
                'booking_date' => now(),
                'departure_date' => $data['departure_date'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'special_requests' => $data['special_requests'] ?? [],
                'tags' => $data['tags'] ?? [],
            ]));
        }

        public function confirmBooking(int $bookingId): bool
        {


            $booking = TravelBooking::findOrFail($bookingId);

            Log::channel('audit')->info('TravelBookingService: Confirming booking', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking) {
                $booking->update(['status' => 'confirmed']);
                return true;
            });
        }

        public function processPayment(int $bookingId, string $paymentMethodId): bool
        {


            $booking = TravelBooking::findOrFail($bookingId);

            Log::channel('audit')->info('TravelBookingService: Processing payment', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
                'payment_method' => $paymentMethodId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking) {
                $booking->update(['payment_status' => 'paid']);

                // Debit from traveler wallet
                $this->walletService->debit(
                    tenantId: $booking->tenant_id,
                    amount: $booking->total_price,
                    reason: 'travel_booking_paid',
                    correlationId: $booking->correlation_id,
                );

                return true;
            });
        }

        public function issueVouchers(int $bookingId): bool
        {


            $booking = TravelBooking::findOrFail($bookingId);

            Log::channel('audit')->info('TravelBookingService: Issuing vouchers', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking) {
                $booking->update([
                    'vouchers_issued_at' => now(),
                    'voucher_codes' => $this->generateVoucherCodes($booking->participants),
                ]);
                return true;
            });
        }

        public function cancelBooking(int $bookingId, string $reason = ''): bool
        {


            $booking = TravelBooking::findOrFail($bookingId);

            Log::channel('audit')->info('TravelBookingService: Cancelling booking', [
                'correlation_id' => $booking->correlation_id,
                'booking_id' => $bookingId,
                'reason' => $reason,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
    DB::transaction(function () use ($booking, $reason) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);

                // Refund if paid
                if ($booking->payment_status === 'paid') {
                    $this->walletService->credit(
                        tenantId: $booking->tenant_id,
                        amount: (int) ($booking->total_price * 0.95),
                        reason: 'travel_booking_refund',
                        correlationId: $booking->correlation_id,
                    );
                }

                return true;
            });
        }

        public function generateItinerary(int $bookingId): Collection
        {


            $booking = TravelBooking::with('tour')->findOrFail($bookingId);
            return collect($booking->tour->itinerary ?? []);
        }

        private function generateVoucherCodes(int $count): array
        {
            return collect()->times($count, fn () => 'TOUR-' . strtoupper(Str::random(8)))->toArray();
        }
}
