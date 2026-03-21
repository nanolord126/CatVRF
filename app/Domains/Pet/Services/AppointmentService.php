<?php declare(strict_types=1);

namespace App\Domains\Pet\Services;

use App\Domains\Pet\Events\AppointmentBooked;
use App\Domains\Pet\Models\PetAppointment;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AppointmentService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
    ) {}

    public function createAppointment(array $data, string $correlationId = null): PetAppointment
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($data, $correlationId) {
                $this->fraudControl->check([
                    'type' => 'pet_appointment',
                    'amount' => $data['price'] ?? 0,
                    'tenant_id' => tenant()->id,
                ]);

                $appointment = PetAppointment::create([
                    ...$data,
                    'tenant_id' => tenant()->id,
                    'appointment_number' => 'APT-' . now()->format('Ym') . '-' . Str::random(6),
                    'commission_amount' => ($data['price'] ?? 0) * 0.14,
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);

                AppointmentBooked::dispatch($appointment, $correlationId);

                Log::channel('audit')->info('Pet appointment created', [
                    'appointment_id' => $appointment->id,
                    'clinic_id' => $appointment->clinic_id,
                    'vet_id' => $appointment->vet_id,
                    'owner_id' => $appointment->owner_id,
                    'price' => $appointment->price,
                    'commission_amount' => $appointment->commission_amount,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to create pet appointment', [
                'data' => $data,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function completeAppointment(PetAppointment $appointment, string $correlationId = null): PetAppointment
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($appointment, $correlationId) {
                $appointment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'payment_status' => 'paid',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Pet appointment completed', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to complete pet appointment', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function cancelAppointment(PetAppointment $appointment, string $correlationId = null): PetAppointment
    {
        $correlationId ??= Str::uuid()->toString();

        try {
            return DB::transaction(function () use ($appointment, $correlationId) {
                if ($appointment->status === 'completed' || $appointment->status === 'cancelled') {
                    throw new \RuntimeException('Cannot cancel completed or already cancelled appointment');
                }

                $appointment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Refund commission if paid
                if ($appointment->payment_status === 'paid') {
                    $clinic = $appointment->clinic;
                    $wallet = $clinic->owner->wallet;

                    $wallet->lockForUpdate();
                    $commissionAmount = (int)($appointment->commission_amount * 100);

                    $wallet->increment('current_balance', $commissionAmount);

                    BalanceTransaction::create([
                        'tenant_id' => $appointment->tenant_id,
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $commissionAmount,
                        'status' => 'completed',
                        'reference_type' => 'pet_appointment',
                        'reference_id' => $appointment->id,
                        'correlation_id' => $correlationId,
                    ]);
                }

                Log::channel('audit')->info('Pet appointment cancelled', [
                    'appointment_id' => $appointment->id,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        } catch (\Throwable $e) {
            Log::error('Failed to cancel pet appointment', [
                'appointment_id' => $appointment->id,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
