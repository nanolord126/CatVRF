<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PetBoardingService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly WalletService $walletService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createBoardingReservation(array $data): PetBoarding
        {
            $this->logger->info('PetBoardingService: Creating boarding reservation', [
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'pet_clinic_id' => $data['pet_clinic_id'],
                'tenant_id' => tenant()->id,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(fn () => PetBoarding::create([
                'uuid' => Str::uuid(),
                'correlation_id' => $data['correlation_id'] ?? Str::uuid(),
                'tenant_id' => tenant()->id,
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

            $this->logger->info('PetBoardingService: Confirming boarding reservation', [
                'correlation_id' => $reservation->correlation_id,
                'reservation_id' => $reservationId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($reservation) {
                $reservation->update(['status' => 'confirmed']);
                return true;
            });
        }

        public function checkInPet(int $reservationId): bool
        {
            $reservation = PetBoarding::findOrFail($reservationId);

            $this->logger->info('PetBoardingService: Pet check-in', [
                'correlation_id' => $reservation->correlation_id,
                'reservation_id' => $reservationId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($reservation) {
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

            $this->logger->info('PetBoardingService: Pet check-out', [
                'correlation_id' => $reservation->correlation_id,
                'reservation_id' => $reservationId,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($reservation, $notes) {
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
            $roomTypes = ['standard', \App\Domains\Wallet\Enums\BalanceTransactionType::PAYOUT, $correlationId, null, null, [$checkInDate, $checkOutDate])
                    ->where('status', \App\Domains\Wallet\Enums\BalanceTransactionType::REFUND, $reservation, null, null, [
                'correlation_id' => $reservation->correlation_id,
                'reservation_id' => $reservationId,
                'reason' => $reason,
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($reservation, $reason) {
                $reservation->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                ]);

                return true;
            });
        }
}
