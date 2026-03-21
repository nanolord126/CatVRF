<?php declare(strict_types=1);

namespace App\Domains\PetServices\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use App\Domains\PetServices\Models\PetBoarding;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class PetBoardingService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function createBoardingReservation(array $data): PetBoarding
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'createBoardingReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createBoardingReservation', ['domain' => __CLASS__]);

        Log::channel('audit')->info('PetBoardingService: Creating boarding reservation', [
            'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
            'pet_clinic_id' => $data['pet_clinic_id'],
            'tenant_id' => filament()->getTenant()->id,
        ]);

        return DB::transaction(fn () => PetBoarding::create([
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'confirmBoardingReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL confirmBoardingReservation', ['domain' => __CLASS__]);

        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Confirming boarding reservation', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        return DB::transaction(function () use ($reservation) {
            $reservation->update(['status' => 'confirmed']);
            return true;
        });
    }

    public function checkInPet(int $reservationId): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'checkInPet'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL checkInPet', ['domain' => __CLASS__]);

        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Pet check-in', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        return DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
            ]);
            return true;
        });
    }

    public function checkOutPet(int $reservationId, array $notes = []): bool
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'checkOutPet'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL checkOutPet', ['domain' => __CLASS__]);

        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Pet check-out', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
        ]);

        return DB::transaction(function () use ($reservation, $notes) {
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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getAvailableRooms'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getAvailableRooms', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'cancelBoardingReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelBoardingReservation', ['domain' => __CLASS__]);

        $reservation = PetBoarding::findOrFail($reservationId);

        Log::channel('audit')->info('PetBoardingService: Cancelling boarding reservation', [
            'correlation_id' => $reservation->correlation_id,
            'reservation_id' => $reservationId,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($reservation, $reason) {
            $reservation->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            return true;
        });
    }
}
