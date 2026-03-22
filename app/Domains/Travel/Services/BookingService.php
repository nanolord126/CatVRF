<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Travel\Events\TourBooked;
use App\Domains\Travel\Models\TravelBooking;
use App\Domains\Travel\Models\TravelTour;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class BookingService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,) {}

    public function createBooking(
        TravelTour $tour,
        User $user,
        int $participantsCount,
        array $participantsData,
        string $correlationId = null,
    ): TravelBooking {


        $correlationId ??= Str::uuid()->toString();

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use (
                $tour,
                $user,
                $participantsCount,
                $participantsData,
                $correlationId,
            ) {
                $pricePerPerson = $tour->price;
                $totalAmount = $pricePerPerson * $participantsCount;
                $commissionAmount = $totalAmount * 0.14;

                $booking = TravelBooking::create([
                    'tenant_id' => tenant()->id,
                    'agency_id' => $tour->agency_id,
                    'tour_id' => $tour->id,
                    'user_id' => $user->id,
                    'booking_number' => 'TB' . date('Ymd') . Str::random(6),
                    'participants_count' => $participantsCount,
                    'price_per_person' => $pricePerPerson,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'participants_data' => $participantsData,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'booked_at' => now(),
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                Log::channel('audit')->info('Tour booking created', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'tour_id' => $tour->id,
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'commission_amount' => $commissionAmount,
                    'correlation_id' => $correlationId,
                    'timestamp' => now(),
                ]);

                TourBooked::dispatch($booking, $correlationId);

                return $booking;
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Tour booking creation failed', [
                'tour_id' => $tour->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function completeBooking(
        TravelBooking $booking,
        string $correlationId = null,
    ): TravelBooking {


        $correlationId ??= $booking->correlation_id;

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Tour booking completed', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'correlation_id' => $correlationId,
                    'timestamp' => now(),
                ]);

                return $booking->refresh();
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Tour booking completion failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function cancelBooking(
        TravelBooking $booking,
        string $reason = null,
        string $correlationId = null,
    ): TravelBooking {


        $correlationId ??= $booking->correlation_id;

        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
DB::transaction(function () use ($booking, $reason, $correlationId) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Tour booking cancelled', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'reason' => $reason,
                    'refund_amount' => $booking->total_amount,
                    'correlation_id' => $correlationId,
                    'timestamp' => now(),
                ]);

                return $booking->refresh();
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Tour booking cancellation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
