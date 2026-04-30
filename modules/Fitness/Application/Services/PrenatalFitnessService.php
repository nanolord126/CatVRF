<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalHealthProfile;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalProgram;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalEnrollment;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalSessionLog;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalHealthProfileRepositoryInterface;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalEnrollmentRepositoryInterface;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalProgramRepositoryInterface;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalSessionLogRepositoryInterface;
{
    public function __construct(
        private PrenatalHealthProfileRepositoryInterface $healthProfileRepository,
        private PrenatalSessionLogRepositoryInterface $sessionLogRepository,
    ) {}

    public function createHealthProfile(
        int $tenantId,
 i    CarbonImmutable $dueDate,
        string $trimester = 'first',
        bool $hasHighRiskPregnancy = false,
        bool $hasPreeclampsiaRisk = false,
        bool $hasGestationalDiabetes = false,
        ?string $obstetricianNotes = null,
        ?string $medications = null,
        ?string $allergies = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        ?array $physicalLimitations = null,
    ): PrenatalHealthProfile {
        $profile = PrenatalHealthProfile::create(
            tenantId: $tenantId,
            clientId: $clientId,
            dueDate: $dueDate,
            trimester: $trimester,
            hasHighRiskPregnancy: $hasHighRiskPregnancy,
            hasPreeclampsiaRisk: $hasPreeclampsiaRisk,
            hasGestationalDiabetes: $hasGestationalDiabetes,
            obstetricianNotes: $obstetricianNotes,
            medications: $medications,
            allergies: $allergies,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            physicalLimitations: $physicalLimitations,
        );

        $saved = $this->healthProfileRepository->save($profile);

        $this->logCreated('PrenatalHealthProfile', $saved->id, [
            'client_id' => $clientId,
            'trimester' => $trimester,
            'due_date' => $dueDate->toDateString(),
        ], null, $tenantId);

        return $saved;
    }

    public function getHealthProfileByClientId(int $clientId): ?PrenatalHealthProfile
    {
        return $this->healthProfileRepository->findByClientId($clientId);
    }

    public function createPrenatalProgram(
        int $tenantId,
        string $name,
        string $description,
        string $targetTrimester,
        int $durationWeeks,
        int $sessionsPerWeek,
        int $sessionDurationMinutes,
        float $price,
        ?int $maxParticipants = null,
        ?array $exercises = null,
        ?array $safetyGuidelines = null,
    ): PrenatalProgram {
        $program = PrenatalProgram::create(
            tenantId: $tenantId,
            name: $name,
            description: $description,
            targetTrimester: $targetTrimester,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            sessionDurationMinutes: $sessionDurationMinutes,
            price: $price,
            maxParticipants: $maxParticipants,
            exercises: $exercises,
        $sav d =fetyGuidelines: $safetyGuidelines,;

        $this->logCreated('PrenatalProgram', $saved->id, [
            'name' => $name,
            'target_trimester' => $targetTrimester,
        ], null, $tenantId);

        return $saved
        );

        return $this->programRepository->save($program);
    }

    public function getActivePrenatalPrograms(int $tenantId): array
    {
        return $this->programRepository->findActiveByTenantId($tenantId);
    }

    public function getProgramsByTrimester(int $tenantId, string $trimester): array
    {
        return $this->programRepository->findByTargetTrimester($tenantId, $trimester);
    }

    public function enrollPrenatal(
        int $tenantId,
        int $clientId,
        int $prenatalProgramId,
        CarbonImmutable $startDate,
        ?array $initialAssessment = null,
    ): PrenatalEnrollment {
        $healthProfile = $this->healthProfileRepository->findByClientId($clientId);
        
        if ($healthProfile !== null && $healthProfile->requiresMedicalClearance()) {
            $medicalClearanceStatus = 'pending';
        } else {
            $medicalClearanceStatus = 'approved';
        }

        $enrollment = PrenatalEnrollment::create(
            tenantId: $tenantId,
            clientId: $clientId,
            prenatalProgramId: $prenatalProgramId,
            startDate: $startDate,
            medicalClearanceStatus: $medicalClearanceStatus,
            initialAssessment: $initialAssessment,
        );

        return $this->enrollmentRepository->save($enrollment);
    }

    public function approveMedicalClearance(int $enrollmentId): PrenatalEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $approved = $enrollment->approveMedicalClearance();
        return $this->enrollmentRepository->save($approved);
    }

    public function updateEnrollmentProgress(int $enrollmentId, float $percent): PrenatalEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $updated = $enrollment->updateProgress($percent);
        return $this->enrollmentRepository->save($updated);
    }

    public function completeEnrollment(int $enrollmentId): PrenatalEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $completed = $enrollment->complete();
        return $this->enrollmentRepository->save($completed);
    }

    public function logSession(
        int $tenantId,
        int $enrollmentId,
        CarbonImmutable $sessionDate,
        ?string $exerciseType = null,
        ?int $durationMinutes = null,
        ?int $heartRateBefore = null,
        ?int $heartRateAfter = null,
        ?int $bloodPressureSystolic = null,
        ?int $bloodPressureDiastolic = null,
        ?string $notes = null,
        ?array $vitals = null,
    ): PrenatalSessionLog {
        $log = PrenatalSessionLog::create(
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            sessionDate: $sessionDate,
            exerciseType: $exerciseType,
            durationMinutes: $durationMinutes,
            heartRateBefore: $heartRateBefore,
            heartRateAfter: $heartRateAfter,
            bloodPressureSystolic: $bloodPressureSystolic,
            bloodPressureDiastolic: $bloodPressureDiastolic,
            notes: $notes,
            vitals: $vitals,
        );

        $savedLog = $this->sessionLogRepository->save($log);

        if ($savedLog->hasAbnormalVitals()) {
            // In production, this would trigger an alert
        }

        return $savedLog;
    }

    public function getSessionLogs(int $enrollmentId): array
    {
        return $this->sessionLogRepository->findByEnrollmentId($enrollmentId);
    }

    public function getPendingClearances(int $tenantId): array
    {
        return $this->enrollmentRepository->findPendingClearance($tenantId);
    }
}
