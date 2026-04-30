<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Senior\Entities\SeniorHealthProfile;
use Modules\Fitness\Domain\Senior\Entities\SeniorProgram;
use Modules\Fitness\Domain\Senior\Entities\SeniorProgramEnrollment;
use Modules\Fitness\Domain\Senior\Entities\SeniorSessionLog;
use Modules\Fitness\Domain\Senior\Repositories\SeniorHealthProfileRepositoryInterface;
use Modules\Fitness\Domain\Senior\Repositories\SeniorProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Domain\Senior\Repositories\SeniorProgramRepositoryInterface;
use Modules\Fitness\Domain\Senior\Repositories\SeniorSessionLogRepositoryInterface;

final readonly class SeniorFitnessService
{
    public function __construct(
        private SeniorHealthProfileRepositoryInterface $healthProfileRepository,
        private SeniorProgramRepositoryInterface $programRepository,
        private SeniorProgramEnrollmentRepositoryInterface $enrollmentRepository,
        private SeniorSessionLogRepositoryInterface $sessionLogRepository,
    ) {}

    public function createHealthProfile(
        int $tenantId,
        int $clientId,
        bool $hasHeartCondition = false,
        bool $hasDiabetes = false,
        bool $hasJointProblems = false,
        bool $hasMobilityLimitations = false,
        bool $hasBalanceIssues = false,
        ?string $medications = null,
        ?string $allergies = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        string $fitnessLevel = 'beginner',
        ?array $physicalLimitations = null,
    ): SeniorHealthProfile {
        $profile = SeniorHealthProfile::create(
            tenantId: $tenantId,
            clientId: $clientId,
            hasHeartCondition: $hasHeartCondition,
            hasDiabetes: $hasDiabetes,
            hasJointProblems: $hasJointProblems,
            hasMobilityLimitations: $hasMobilityLimitations,
            hasBalanceIssues: $hasBalanceIssues,
            medications: $medications,
            allergies: $allergies,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            fitnessLevel: $fitnessLevel,
            physicalLimitations: $physicalLimitations,
        );

        return $this->healthProfileRepository->save($profile);
    }

    public function getHealthProfileByClientId(int $clientId): ?SeniorHealthProfile
    {
        return $this->healthProfileRepository->findByClientId($clientId);
    }

    public function createSeniorProgram(
        int $tenantId,
        string $name,
        string $description,
        string $focusArea,
        int $durationWeeks,
        int $sessionsPerWeek,
        int $sessionDurationMinutes,
        float $price,
        ?int $maxParticipants = null,
        ?array $exercises = null,
        ?array $safetyRequirements = null,
    ): SeniorProgram {
        $program = SeniorProgram::create(
            tenantId: $tenantId,
            name: $name,
            description: $description,
            focusArea: $focusArea,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            sessionDurationMinutes: $sessionDurationMinutes,
            price: $price,
            maxParticipants: $maxParticipants,
            exercises: $exercises,
            safetyRequirements: $safetyRequirements,
        );

        return $this->programRepository->save($program);
    }

    public function getActiveSeniorPrograms(int $tenantId): array
    {
        return $this->programRepository->findActiveByTenantId($tenantId);
    }

    public function enrollSenior(
        int $tenantId,
        int $clientId,
        int $seniorProgramId,
        CarbonImmutable $startDate,
        ?array $initialAssessment = null,
    ): SeniorProgramEnrollment {
        $healthProfile = $this->healthProfileRepository->findByClientId($clientId);
        
        if ($healthProfile !== null && $healthProfile->requiresMedicalClearance()) {
            $medicalClearanceStatus = 'pending';
        } else {
            $medicalClearanceStatus = 'approved';
        }

        $enrollment = SeniorProgramEnrollment::create(
            tenantId: $tenantId,
            clientId: $clientId,
            seniorProgramId: $seniorProgramId,
            startDate: $startDate,
            medicalClearanceStatus: $medicalClearanceStatus,
            initialAssessment: $initialAssessment,
        );

        return $this->enrollmentRepository->save($enrollment);
    }

    public function approveMedicalClearance(int $enrollmentId): SeniorProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $approved = $enrollment->approveMedicalClearance();
        return $this->enrollmentRepository->save($approved);
    }

    public function updateEnrollmentProgress(int $enrollmentId, float $percent): SeniorProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $updated = $enrollment->updateProgress($percent);
        return $this->enrollmentRepository->save($updated);
    }

    public function completeEnrollment(int $enrollmentId): SeniorProgramEnrollment
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
        ?int $perceivedExertion = null,
        ?string $notes = null,
        ?array $vitals = null,
    ): SeniorSessionLog {
        $log = SeniorSessionLog::create(
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            sessionDate: $sessionDate,
            exerciseType: $exerciseType,
            durationMinutes: $durationMinutes,
            heartRateBefore: $heartRateBefore,
            heartRateAfter: $heartRateAfter,
            bloodPressureSystolic: $bloodPressureSystolic,
            bloodPressureDiastolic: $bloodPressureDiastolic,
            perceivedExertion: $perceivedExertion,
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
