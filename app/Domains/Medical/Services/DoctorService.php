<?php declare(strict_types=1);

namespace App\Domains\Medical\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class DoctorService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function createDoctor(
            int $tenantId,
            int $clinicId,
            int $userId,
            string $fullName,
            string $specialization,
            int $experienceYears,
            ?string $bio,
            ?string $licenseNumber,
            ?string $correlationId = null
    ): MedicalDoctor {
            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use (
                    $tenantId,
                    $clinicId,
                    $userId,
                    $fullName,
                    $specialization,
                    $experienceYears,
                    $bio,
                    $licenseNumber,
                    $correlationId
    ) {
                    $doctor = MedicalDoctor::create([
                        'tenant_id' => $tenantId,
                        'clinic_id' => $clinicId,
                        'user_id' => $userId,
                        'full_name' => $fullName,
                        'specialization' => $specialization,
                        'experience_years' => $experienceYears,
                        'bio' => $bio,
                        'license_number' => $licenseNumber,
                        'is_active' => true,
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Medical doctor created', [
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $clinicId,
                        'specialization' => $specialization,
                        'correlation_id' => $correlationId,
                    ]);

                    return $doctor;
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to create doctor', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function updateDoctor(
            MedicalDoctor $doctor,
            array $data,
            ?string $correlationId = null
    ): MedicalDoctor {
            $correlationId ??= Str::uuid()->toString();

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($doctor, $data, $correlationId) {
                    $doctor->update([...$data, 'correlation_id' => $correlationId]);

                    $this->logger->info('Medical doctor updated', [
                        'doctor_id' => $doctor->id,
                        'correlation_id' => $correlationId,
                    ]);

                    return $doctor;
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to update doctor', [
                    'doctor_id' => $doctor->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
