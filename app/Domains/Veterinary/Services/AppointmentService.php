<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class AppointmentService
{

    private readonly string $correlationId;


    public function __construct(private FraudControlService $fraud,
            string $correlationId = '',
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {

    }

        private function getCorrelationId(): string
        {
            return $this->correlationId ?: Str::uuid()->toString();
        }

        /**
         * Create appointment with strict validation
         */
        public function create(array $data): VeterinaryAppointment
        {
            $correlationId = $this->getCorrelationId();

            $this->logger->info('AppointmentService: Creating appointment', [
                'data' => $data,
                'correlation_id' => $correlationId
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($data, $correlationId) {
                $appointment = VeterinaryAppointment::create(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'status' => 'pending'
                ]));

                $this->logger->info('AppointmentService: Appointment created', [
                    'id' => $appointment->id,
                    'correlation_id' => $correlationId
                ]);

                return $appointment;
            });
        }

        /**
         * Cancel appointment with reason
         */
        public function cancel(int $id, string $reason): bool
        {
            $correlationId = $this->getCorrelationId();

            return $this->db->transaction(function () use ($id, $reason, $correlationId) {
                $appointment = VeterinaryAppointment::findOrFail($id);

                $this->logger->warning('AppointmentService: Cancelling appointment', [
                    'id' => $id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId
                ]);

                return $appointment->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'correlation_id' => $correlationId
                ]);
            });
        }

        /**
         * Mark as completed and trigger medical record creation
         */
        public function complete(int $id): void
        {
            $correlationId = $this->getCorrelationId();

            $this->db->transaction(function () use ($id, $correlationId) {
                $appointment = VeterinaryAppointment::findOrFail($id);
                $appointment->update([
                    'status' => 'completed',
                    'correlation_id' => $correlationId
                ]);

                $this->logger->info('AppointmentService: Appointment completed', [
                    'id' => $id,
                    'correlation_id' => $correlationId
                ]);
            });
        }
}
