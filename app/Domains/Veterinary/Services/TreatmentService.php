<?php declare(strict_types=1);

namespace App\Domains\Veterinary\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TreatmentService
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
         * Create a medical record for a pet
         */
        public function createRecord(array $data): MedicalRecord
        {
            $correlationId = $this->getCorrelationId();

            $this->logger->info('TreatmentService: Creating medical record', [
                'pet_id' => $data['pet_id'] ?? 'unknown',
                'correlation_id' => $correlationId
            ]);

            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($data, $correlationId) {
                $record = MedicalRecord::create(array_merge($data, [
                    'correlation_id' => $correlationId
                ]));

                $this->logger->info('TreatmentService: Medical record created', [
                    'id' => $record->id,
                    'correlation_id' => $correlationId
                ]);

                return $record;
            });
        }

        /**
         * Get full pet medical history
         */
        public function getPetHistory(int $petId): \Illuminate\Support\Collection
        {
            return MedicalRecord::where('pet_id', $petId)->orderByDesc('created_at')->get();
        }

        /**
         * Schedule next visit as recommendation
         */
        public function scheduleFollowUp(int $petId, int $veterinarianId, \DateTimeInterface $at): void
        {
            $correlationId = $this->getCorrelationId();

            $this->logger->info('TreatmentService: Scheduling follow-up', [
                'pet_id' => $petId,
                'at' => $at->format('Y-m-d H:i:s'),
                'correlation_id' => $correlationId
            ]);

            // This could trigger a notification/email to the pet owner
        }
}
