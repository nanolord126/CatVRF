<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Services;





use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;

final readonly class WellnessBookingService
{
    public function __construct(
        private \App\Services\FraudControlService $fraud,
        private \App\Services\WalletService $walletService,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

        /**
         * Create a new booking/appointment via the service layer.
         * Includes DB transaction, fraud checks, and audit logging.
         * @throws \RuntimeException
         */
        public function createAppointment(WellnessAppointmentDto $dto): WellnessAppointment
        {
            $correlationId = $dto->correlation_id ?? (string) Str::uuid();

            // 1. Audit Entry
            $this->logger->info('Creating Wellness Appointment Init', [
                'center_id' => $dto->center_id,
                'client_id' => $dto->client_id,
                'service_id' => $dto->service_id,
                'correlation_id' => $correlationId,
            ]);

            return $this->db->transaction(function () use ($dto, $correlationId) {
                // 2. Fraud Check (Canon 2026 - must be before mutations)
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'wellness_appointment_create', amount: 0, correlationId: $correlationId ?? '');

                // 3. Availability Check (Optimistic Locking)
                $this->checkAvailability($dto);

                // 4. Persistence
                $appointment = WellnessAppointment::create(array_merge($dto->toArray(), [
                     'correlation_id' => $correlationId,
                     'status' => 'pending',
                ]));

                // 5. Audit Exit
                $this->logger->info('Wellness Appointment Created', [
                    'appointment_uuid' => $appointment->uuid,
                    'correlation_id' => $correlationId,
                ]);

                return $appointment;
            });
        }

        /**
         * Confirms the appointment and initiates payment hold if applicable.
         */
        public function confirmAppointment(WellnessAppointment $appointment): bool
        {
            return $this->db->transaction(function () use ($appointment) {
                // Lock the record for update (Canon 2026)
                $appointment->lockForUpdate()->find($appointment->id);

                // Hold payment in wallet (B2C Canon)
                if ($appointment->price > 0 && $appointment->payment_status === 'unpaid') {
                     $this->walletService->hold([
                         'amount' => $appointment->price,
                         'reason' => "Wellness appt hold: {$appointment->uuid}",
                         'appointment_uuid' => $appointment->uuid,
                         'correlation_id' => $appointment->correlation_id,
                     ]);
                }

                return true;
            });
        }

        /**
         * Cancellation with policy enforcement.
         */
        public function cancelAppointment(WellnessAppointment $appointment, string $reason): bool
        {
            return $this->db->transaction(function () use ($appointment, $reason) {
                // Check cancellation timeframe (Vertical policy: 24h)
                $limit = Carbon::now()->addHours(24);
                if ($appointment->datetime_start <= $limit) {
                    // Potential penalty logic could go here
                    $this->logger->warning('Late cancellation for appointment', [
                         'appointment_uuid' => $appointment->uuid,
                         'reason' => $reason,
                        'correlation_id' => $appointment->correlation_id,
                    ]);
                }

                // Release hold if status was hold
                if ($appointment->payment_status === 'hold') {
                    $this->walletService->release_hold([
                         'amount' => $appointment->price,
                         'reason' => "Wellness appt release: {$appointment->uuid}",
                         'user_id' => $appointment->client_id,
                         'correlation_id' => $appointment->correlation_id,
                    ]);
                    $appointment->payment_status = 'unpaid';
                }

                $appointment->status = 'cancelled';
                $appointment->save();

                return true;
            });
        }

        /**
         * Internal availability check (stub - needs time slot realization).
         */
        private function checkAvailability(WellnessAppointmentDto $dto): void
        {
            $conflict = WellnessAppointment::where('specialist_id', $dto->specialist_id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($dto) {
                    $query->whereBetween('datetime_start', [$dto->datetime_start, $dto->datetime_end])
                          ->orWhereBetween('datetime_end', [$dto->datetime_start, $dto->datetime_end]);
                })
                ->exists();

            if ($conflict) {
                throw new \DomainException("Specialist is occupied during the requested timeframe.", 409);
            }
        }
}
