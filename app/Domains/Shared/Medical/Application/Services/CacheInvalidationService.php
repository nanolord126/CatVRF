<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Application\Services;

use Illuminate\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Application Service: Handles cache invalidation for medical entities
 */
final readonly class CacheInvalidationService
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}
    public function invalidateAppointmentCache(
        string $appointmentId,
        string $patientId,
        string $doctorId,
    ): void {
        try {
            // Invalidate appointment-specific cache
            $this->cache->tags(['appointments', "appointment:{$appointmentId}"])->flush();

            // Invalidate patient's appointments cache
            $this->cache->tags(['patients', "patient:{$patientId}", "patient:{$patientId}:appointments"])->flush();

            // Invalidate doctor's schedule cache
            $this->cache->tags(['doctors', "doctor:{$doctorId}", "doctor:{$doctorId}:schedule"])->flush();

            $this->logger->$this->logger->info('Cache invalidated for appointment', [
                'appointment_id' => $appointmentId,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate cache for appointment', [
                'appointment_id' => $appointmentId,
                'error' => $e->getMessage(),
            ]);
            // Non-critical error, don't throw
        }
    }

    public function invalidateMedicalRecordCache(string $recordId, string $patientId): void
    {
        $this->cache->tags(['medical_records', "record:{$recordId}", "patient:{$patientId}:records"])->flush();
    }

    public function invalidateDoctorCache(string $doctorId): void
    {
        $this->cache->tags(['doctors', "doctor:{$doctorId}"])->flush();
    }

    public function invalidateClinicCache(string $clinicId): void
    {
        $this->cache->tags(['clinics', "clinic:{$clinicId}"])->flush();
    }
}
