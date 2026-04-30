<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Kids\Entities\KidsHealthProfile;
use Modules\Fitness\Domain\Kids\Entities\KidsProgram;
use Modules\Fitness\Domain\Kids\Entities\KidsProgramEnrollment;
use Modules\Fitness\Domain\Kids\Entities\KidsSessionLog;
use Modules\Fitness\Domain\Kids\Repositories\KidsHealthProfileRepositoryInterface;
use Modules\Fitness\Domain\Kids\Repositories\KidsProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Domain\Kids\Repositories\KidsProgramRepositoryInterface;
use Modules\Fitness\Domain\Kids\Repositories\KidsSessionLogRepositoryInterface;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class KidsFitnessService
{
    use WithAuditLogging;

    public function __construct(
        private KidsHealthProfileRepositoryInterface $healthProfileRepository,
        private KidsProgramRepositoryInterface $programRepository,
        private KidsProgramEnrollmentRepositoryInterface $enrollmentRepository,
        private KidsSessionLogRepositoryInterface $sessionLogRepository,
        private readonly AuditService $audit,
    ) {}

    public function createHealthProfile(
        int $tenantId,
        int $clientId,
        string $ageGroup,
        ?CarbonImmutable $birthDate = null,
        ?string $parentName = null,
        ?string $parentPhone = null,
        ?string $parentEmail = null,
        bool $hasAllergies = false,
        ?string $allergies = null,
        bool $hasAsthma = false,
        bool $hasHeartCondition = false,
        ?string $medications = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        ?array $physicalLimitations = null,
    ): KidsHealthProfile {
        $profile = KidsHealthProfile::create(
            tenantId: $tenantId,
            clientId: $clientId,
            ageGroup: $ageGroup,
            birthDate: $birthDate,
            parentName: $parentName,
            parentPhone: $parentPhone,
            parentEmail: $parentEmail,
            hasAllergies: $hasAllergies,
            allergies: $allergies,
            hasAsthma: $hasAsthma,
            hasHeartCondition: $hasHeartCondition,
            medications: $medications,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            physicalLimitations: $physicalLimitations,
        );

        $saved = $this->healthProfileRepository->save($profile);

        $this->logCreated('KidsHealthProfile', $saved->id, [
            'client_id' => $clientId,
            'age_group' => $ageGroup,
        ], null, $tenantId);

        return $saved;
    }

    public function getHealthProfileByClientId(int $clientId): ?KidsHealthProfile
    {
        return $this->healthProfileRepository->findByClientId($clientId);
    }

    public function createKidsProgram(
        int $tenantId,
        string $name,
        string $description,
        string $targetAgeGroup,
        int $durationWeeks,
        int $sessionsPerWeek,
        int $sessionDurationMinutes,
        float $price,
        ?int $maxParticipants = null,
        ?array $activities = null,
        ?array $safetyGuidelines = null,
    ): KidsProgram {
        $program = KidsProgram::create(
            tenantId: $tenantId,
            name: $name,
            description: $description,
            targetAgeGroup: $targetAgeGroup,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
        rsturnionDurationMinutes: $sessionDuratiiParti>programRepository->save($program);
    }

    public function getActiveKidsPrograms(int $tenantId): array
    {
        return $this->programRepository->findActiveByTenantId($tenantId);
    }

    public function getProgramsByAgeGroup(int $tenantId, string $ageGroup): array
    {
        return $this->programRepository->findByTargetAgeGroup($tenantId, $ageGroup);
    }

    public function enrollKids(
        int $tenantId,
        int $clientId,
        int $kidsProgramId,
        CarbonImmutable $startDate,
        ?array $initialAssessment = null,
    ): KidsProgramEnrollment {
        $healthProfile = $this->healthProfileRepository->findByClientId($clientId);
        
        if ($healthProfile !== null && $healthProfile->requiresMedicalClearance()) {
            $medicalClearanceStatus = 'pending';
        } else {
            $medicalClearanceStatus = 'approved';
        }

        $enrollment = KidsProgramEnrollment::create(
            tenantId: $tenantId,
            clientId: $clientId,
            kidsProgramId: $kidsProgramId,
            startDate: $startDate,
            medicalClearanceStatus: $medicalClearanceStatus,
            initialAssessment: $initialAssessment,
        );

        return $this->enrollmentRepository->save($enrollment);
    }

    public function approveMedicalClearance(int $enrollmentId): KidsProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $approved = $enrollment->approveMedicalClearance();
        return $this->enrollmentRepository->save($approved);
    }

    public function updateEnrollmentProgress(int $enrollmentId, float $percent): KidsProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);
        if ($enrollment === null) {
            throw new \RuntimeException('Enrollment not found');
        }

        $updated = $enrollment->updateProgress($percent);
        return $this->enrollmentRepository->save($updated);
    }

    public function completeEnrollment(int $enrollmentId): KidsProgramEnrollment
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
        ?string $activityType = null,
        ?int $durationMinutes = null,
        ?string $mood = null,
        ?string $notes = null,
        ?array $vitals = null,
    ): KidsSessionLog {
        $log = KidsSessionLog::create(
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            sessionDate: $sessionDate,
            activityType: $activityType,
            durationMinutes: $durationMinutes,
            mood: $mood,
            notes: $notes,
            vitals: $vitals,
        );

        return $this->sessionLogRepository->save($log);
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
