<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Domains\Pet\Events\BoardingReservationCreated;
use App\Domains\Pet\Models\PetBoardingReservation;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class BoardingService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
    ) {}

    public function createReservation(array $data, string $correlationId = null): PetBoardingReservation
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'createReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL createReservation', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check([
                    'type' => 'pet_boarding',
                    'amount' => $data['total_amount'] ?? 0,
                    'tenant_id' => tenant()->id,
                ]);

                $reservation = PetBoardingReservation::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'reservation_number' => 'BRD-' . now()->format('Ym') . '-' . Str::random(6),
                    'commission_amount' => ($data['total_amount'] ?? 0) * 0.14,
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                BoardingReservationCreated::dispatch($reservation, $correlationId);

                Log::channel('audit')->info('Pet boarding reservation created', [
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
            Log::error('Failed to create boarding reservation', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'completeReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL completeReservation', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($reservation, $correlationId) {
                $reservation->update([
                    'status' => 'completed',
                    'actual_check_out' => now(),
                    'payment_status' => 'paid',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Pet boarding reservation completed', [
                    'reservation_id' => $reservation->id,
                    'correlation_id' => $correlationId,
                ]);

                return $reservation;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to complete boarding reservation', [
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'cancelReservation'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL cancelReservation', ['domain' => __CLASS__]);

        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($reservation, $correlationId) {
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

                Log::channel('audit')->info('Pet boarding reservation cancelled', [
                    'reservation_id' => $reservation->id,
                    'correlation_id' => $correlationId,
                ]);

                return $reservation;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to cancel boarding reservation', [
                'reservation_id' => $reservation->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
