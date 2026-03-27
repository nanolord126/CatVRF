<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;

use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\Pet\PetServices\Models\PetBoarding;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PetBoardingService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly WalletService $walletService,
    ) {}

    public function createBoardingReservation(array $data): PetBoarding
    {
        Log::channel('audit')->info('PetBoardingService: Creating boarding reservation', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'pet_clinic_id' => $data['pet_clinic_id'],
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
DB::transaction(fn () => PetBoarding::create([
            'uuid' => Str::uuid(),
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'tenant_id' => filament()->getTenant()->id,
            'pet_clinic_id' => $data['pet_clinic_id'],
            'pet_id' => $data['pet_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'room_type' => $data['room_type'] ?? 'standard',
            'daily_price' => $data['daily_price'] ?? 200000,
            'total_price' => $data['total_price'],
            'status' => 'pending',
            'special_requests' => $data['special_requests'] ?? [],
            'tags' => $data['tags'] ?? [],
        ]));
    }

    public function confirmBoardingReservation(int $reservationId): bool
    {
        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Confirming boarding reservation', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'confirmed']);
            return true;
        });
    }

    public function checkInPet(int $reservationId): bool
    {
        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Pet check-in', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
            ]);
            return true;
        });
    }

    public function checkOutPet(int $reservationId, array $notes = []): bool
    {
        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Pet check-out', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
DB::transaction(function () use ($reservation, $notes) {
            $reservation->update([
                'status' => 'completed',
                'actual_check_out' => now(),
                'checkout_notes' => $notes,
            ]);

            // Credit to clinic wallet
            $this->walletService->credit(
                tenantId: $reservation->tenant_id,
                amount: (int) ($reservation->total_price * 0.86),
                reason: 'boarding_service_completed',
                correlationId: $reservation->correlation_id,
            );

            return true;
        });
    }

    public function getAvailableRooms(string $checkInDate, string $checkOutDate): Collection
    {
        $roomTypes = ['standard', 'premium', 'luxury'];

        return collect($roomTypes)->map(function (string $type) use ($checkInDate, $checkOutDate) {
            $occupied = PetBoarding::where('room_type', $type)
                ->whereBetween('check_in_date', [$checkInDate, $checkOutDate])
                ->where('status', '!=', 'cancelled')
                ->count();

            return [
                'room_type' => $type,
                'available' => $occupied < 5,
                'price_multiplier' => match ($type) {
                    'premium' => 1.5,
                    'luxury' => 2.0,
                    default => 1.0,
                },
            ];
        });
    }

    public function cancelBoardingReservation(int $reservationId, string $reason = ''): bool
    {
        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Cancelling boarding reservation', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
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
DB::transaction(function () use ($reservation, $reason) {
            $reservation->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            return true;
        });
    }
}
