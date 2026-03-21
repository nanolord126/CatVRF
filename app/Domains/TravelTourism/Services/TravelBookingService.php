<?php declare(strict_types=1);

namespace App\Domains\TravelTourism\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\TravelTourism\Models\TravelBooking;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TravelBookingService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function createBooking(array $data): TravelBooking
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createBooking', ['domain' => __CLASS__]);

        Log::channel('audit')->info('TravelBookingService: Creating travel booking', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'tour_id' => $data['tour_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(fn () => TravelBooking::create([
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'confirmBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL confirmBooking', ['domain' => __CLASS__]);

        $booking = TravelBooking::findOrFail($bookingId);

        Log::channel('audit')->info('TravelBookingService: Confirming booking', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'confirmed']);
            return true;
        });
    }

    public function processPayment(int $bookingId, string $paymentMethodId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'processPayment'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL processPayment', ['domain' => __CLASS__]);

        $booking = TravelBooking::findOrFail($bookingId);

        Log::channel('audit')->info('TravelBookingService: Processing payment', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
            'payment_method' => $paymentMethodId,
        ]);

        return DB::transaction(function () use ($booking) {
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'issueVouchers'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL issueVouchers', ['domain' => __CLASS__]);

        $booking = TravelBooking::findOrFail($bookingId);

        Log::channel('audit')->info('TravelBookingService: Issuing vouchers', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
        ]);

        return DB::transaction(function () use ($booking) {
            $booking->update([
                'vouchers_issued_at' => now(),
                'voucher_codes' => $this->generateVoucherCodes($booking->participants),
            ]);
            return true;
        });
    }

    public function cancelBooking(int $bookingId, string $reason = ''): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelBooking'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelBooking', ['domain' => __CLASS__]);

        $booking = TravelBooking::findOrFail($bookingId);

        Log::channel('audit')->info('TravelBookingService: Cancelling booking', [
            'correlation_id' => $booking->correlation_id,
            'booking_id' => $bookingId,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($booking, $reason) {
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'generateItinerary'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL generateItinerary', ['domain' => __CLASS__]);

        $booking = TravelBooking::with('tour')->findOrFail($bookingId);
        return collect($booking->tour->itinerary ?? []);
    }

    private function generateVoucherCodes(int $count): array
    {
        return collect()->times($count, fn () => 'TOUR-' . strtoupper(Str::random(8)))->toArray();
    }
}
