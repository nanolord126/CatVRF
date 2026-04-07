<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class BoardingService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createReservation(array $data, string $correlationId = null): PetBoardingReservation
        {
            $correlationId ??= Str::uuid()->toString();

            try {
                return $this->db->transaction(function () use ($data, $correlationId) {
                    $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'create_boarding_reservation', amount: 0, correlationId: $correlationId ?? '');

                    $reservation = PetBoardingReservation::create([
                        ...$data,
                        'tenant_id' => tenant()->id,
                        'reservation_number' => 'BRD-' . now()->format('Ym') . '-' . Str::random(6),
                        'commission_amount' => ($data['total_amount'] ?? 0) * 0.14,
                        'correlation_id' => $correlationId,
                        'uuid' => Str::uuid(),
                    ]);

                    BoardingReservationCreated::dispatch($reservation, $correlationId);

                    $this->logger->info('Pet boarding reservation created', [
                        'reservation_id' => $reservation->id,
                        'clinic_id' => $reservation->clinic_id,
                        'owner_id' => $reservation->owner_id,
                        'total_amount' => $reservation->total_amount,
                        'commission_amount' => $reservation->commission_amount,
                        'correlation_id' => $correlationId,
                    ]);

                    return $reservation;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create boarding reservation', [
                    'data' => $data,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        public function completeReservation(PetBoardingReservation $reservation, string $correlationId = null): PetBoardingReservation
        {
            $correlationId ??= Str::uuid()->toString();

            try {
                return $this->db->transaction(function () use ($reservation, $correlationId) {
                    $reservation->update([
                        'status' => 'completed',
                        'actual_check_out' => now(),
                        'payment_status' => 'paid',
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Pet boarding reservation completed', [
                        'reservation_id' => $reservation->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $reservation;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to complete boarding reservation', [
                    'reservation_id' => $reservation->id,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        public function cancelReservation(PetBoardingReservation $reservation, string $correlationId = null): PetBoardingReservation
        {
            $correlationId ??= Str::uuid()->toString();

            try {
                return $this->db->transaction(function () use ($reservation, $correlationId) {
                    if ($reservation->status === 'completed' || $reservation->status === 'cancelled') {
                        throw new \RuntimeException('Cannot cancel completed or already cancelled reservation');
                    }

                    $reservation->update([
                        'status' => 'cancelled',
                        'correlation_id' => $correlationId,
                    ]);

                    // Refund commission if paid
                    if ($reservation->payment_status === 'paid') {
                        $clinic = $reservation->clinic;
                        $wallet = $clinic->owner->wallet;

                        $wallet->lockForUpdate();
                        $commissionAmount = (int)($reservation->commission_amount * 100);

                        $wallet->increment('current_balance', $commissionAmount);

                        BalanceTransaction::create([
                            'tenant_id' => $reservation->tenant_id,
                            'wallet_id' => $wallet->id,
                            'type' => 'refund',
                            'amount' => $commissionAmount,
                            'status' => 'completed',
                            'reference_type' => 'pet_boarding',
                            'reference_id' => $reservation->id,
                            'correlation_id' => $correlationId,
                        ]);
                    }

                    $this->logger->info('Pet boarding reservation cancelled', [
                        'reservation_id' => $reservation->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $reservation;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to cancel boarding reservation', [
                    'reservation_id' => $reservation->id,
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
