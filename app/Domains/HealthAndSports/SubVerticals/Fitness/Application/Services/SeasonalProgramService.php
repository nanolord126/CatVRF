<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\ClientProgramEnrollment;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\ProgramProgressLog;
use Modules\Fitness\Domain\SeasonalPrograms\Entities\SeasonalProgram;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\EnrollmentStatus;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramStatus;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramType;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\ClientProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\ProgramProgressLogRepositoryInterface;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\SeasonalProgramRepositoryInterface;

final readonly class SeasonalProgramService
{
    public function __construct(
        private SeasonalProgramRepositoryInterface $programRepository,
        private ClientProgramEnrollmentRepositoryInterface $enrollmentRepository,
        private ProgramProgressLogRepositoryInterface $progressLogRepository,
    ) {}

    public function createProgram(
        int $tenantId,
        string $name,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $durationWeeks,
        int $sessionsPerWeek,
        float $price,
        SeasonalProgramType $type = SeasonalProgramType::GROUP,
        ?int $maxParticipants = null,
        ?string $description = null,
        ?array $goals = null,
        ?string $requirements = null,
    ): SeasonalProgram {
        $program = SeasonalProgram::create(
            tenantId: $tenantId,
            name: $name,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            durationWeeks: $durationWeeks,
            sessionsPerWeek: $sessionsPerWeek,
            price: $price,
            type: $type,
            maxParticipants: $maxParticipants,
            description: $description,
            goals: $goals,
            requirements: $requirements,
        );

        return $this->programRepository->save($program);
    }

    public function publishProgram(int $programId): SeasonalProgram
    {
        $program = $this->programRepository->findById($programId);

        if ($program === null) {
            throw new \InvalidArgumentException("Program not found: {$programId}");
        }

        $publishedProgram = $program->publish();

        return $this->programRepository->save($publishedProgram);
    }

    public function archiveProgram(int $programId): SeasonalProgram
    {
        $program = $this->programRepository->findById($programId);

        if ($program === null) {
            throw new \InvalidArgumentException("Program not found: {$programId}");
        }

        $archivedProgram = $program->archive();

        return $this->programRepository->save($archivedProgram);
    }

    public function cancelProgram(int $programId): SeasonalProgram
    {
        $program = $this->programRepository->findById($programId);

        if ($program === null) {
            throw new \InvalidArgumentException("Program not found: {$programId}");
        }

        $cancelledProgram = $program->cancel();

        return $this->programRepository->save($cancelledProgram);
    }

    public function enrollClient(
        int $clientId,
        int $programId,
        ?array $initialMetrics = null,
    ): ClientProgramEnrollment {
        $program = $this->programRepository->findById($programId);

        if ($program === null) {
            throw new \InvalidArgumentException("Program not found: {$programId}");
        }

        if (!$program->hasAvailableSlots()) {
            throw new \RuntimeException("Program is full: {$programId}");
        }

        if ($program->status !== SeasonalProgramStatus::ACTIVE) {
            throw new \RuntimeException("Program is not active: {$programId}");
        }

        $existingEnrollment = $this->enrollmentRepository->findByClientIdAndProgramId(
            $clientId,
            $programId
        );

        if ($existingEnrollment !== null) {
            throw new \RuntimeException("Client already enrolled in program: {$programId}");
        }

        return DB::transaction(function () use ($clientId, $programId, $initialMetrics, $program) {
            $enrollment = ClientProgramEnrollment::create(
                tenantId: $program->tenantId,
                clientId: $clientId,
                seasonalProgramId: $programId,
                startDate: CarbonImmutable::now(),
                initialMetrics: $initialMetrics,
            );

            $savedEnrollment = $this->enrollmentRepository->save($enrollment);

            $updatedProgram = $program->incrementParticipants();
            $this->programRepository->save($updatedProgram);

            Log::info('Client enrolled in seasonal program', [
                'enrollment_id' => $savedEnrollment->id,
                'client_id' => $clientId,
                'program_id' => $programId,
            ]);

            return $savedEnrollment;
        });
    }

    public function updateEnrollmentProgress(
        int $enrollmentId,
        float $progressPercent,
    ): ClientProgramEnrollment {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $updatedEnrollment = $enrollment->updateProgress($progressPercent);

        return $this->enrollmentRepository->save($updatedEnrollment);
    }

    public function pauseEnrollment(int $enrollmentId): ClientProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $pausedEnrollment = $enrollment->pause();

        return $this->enrollmentRepository->save($pausedEnrollment);
    }

    public function resumeEnrollment(int $enrollmentId): ClientProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $resumedEnrollment = $enrollment->resume();

        return $this->enrollmentRepository->save($resumedEnrollment);
    }

    public function dropEnrollment(int $enrollmentId, ?string $reason = null): ClientProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        return DB::transaction(function () use ($enrollmentId, $enrollment, $reason) {
            $droppedEnrollment = $enrollment->drop($reason);
            $savedEnrollment = $this->enrollmentRepository->save($droppedEnrollment);

            $program = $this->programRepository->findById($enrollment->seasonalProgramId);
            if ($program !== null) {
                $updatedProgram = $program->decrementParticipants();
                $this->programRepository->save($updatedProgram);
            }

            Log::info('Client dropped from seasonal program', [
                'enrollment_id' => $enrollmentId,
                'reason' => $reason,
            ]);

            return $savedEnrollment;
        });
    }

    public function completeEnrollment(
        int $enrollmentId,
        ?array $finalMetrics = null,
    ): ClientProgramEnrollment {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $completedEnrollment = $enrollment->complete($finalMetrics);

        return $this->enrollmentRepository->save($completedEnrollment);
    }

    public function logProgress(
        int $enrollmentId,
        CarbonImmutable $date,
        ?array $metrics = null,
        ?string $notes = null,
        ?array $photos = null,
        ?float $weight = null,
        ?float $bodyFatPercentage = null,
        ?array $measurements = null,
        ?int $wellbeingScore = null,
        ?int $energyLevel = null,
        ?int $sleepQuality = null,
        bool $completedWorkout = false,
        ?string $trainerNotes = null,
    ): ProgramProgressLog {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $log = ProgramProgressLog::create(
            tenantId: $enrollment->tenantId,
            enrollmentId: $enrollmentId,
            date: $date,
            metrics: $metrics,
            notes: $notes,
            photos: $photos,
            weight: $weight,
            bodyFatPercentage: $bodyFatPercentage,
            measurements: $measurements,
            wellbeingScore: $wellbeingScore,
        );

        $savedLog = $this->progressLogRepository->save($log);

        // Auto-calculate progress based on logs
        $this->recalculateProgress($enrollmentId);

        return $savedLog;
    }

    public function recalculateProgress(int $enrollmentId): ClientProgramEnrollment
    {
        $enrollment = $this->enrollmentRepository->findById($enrollmentId);

        if ($enrollment === null) {
            throw new \InvalidArgumentException("Enrollment not found: {$enrollmentId}");
        }

        $program = $this->programRepository->findById($enrollment->seasonalProgramId);

        if ($program === null) {
            throw new \InvalidArgumentException("Program not found: {$enrollment->seasonalProgramId}");
        }

        $logs = $this->progressLogRepository->findByEnrollmentId($enrollmentId);
        $completedWorkouts = $logs->filter(fn ($log) => $log->completedWorkout)->count();

        $totalSessions = $program->durationWeeks * $program->sessionsPerWeek;
        $progressPercent = $totalSessions > 0
            ? ($completedWorkouts / $totalSessions) * 100
            : 0.0;

        return $this->updateEnrollmentProgress($enrollmentId, $progressPercent);
    }

    public function getProgramById(int $programId): ?SeasonalProgram
    {
        return $this->programRepository->findById($programId);
    }

    public function getActivePrograms(int $tenantId): array
    {
        return $this->programRepository->findActiveByTenantId($tenantId);
    }

    public function getUpcomingPrograms(int $tenantId): array
    {
        return $this->programRepository->findUpcomingByTenantId($tenantId);
    }

    public function getClientEnrollments(int $clientId): array
    {
        return $this->enrollmentRepository->findByClientId($clientId);
    }

    public function getProgramEnrollments(int $programId): array
    {
        return $this->enrollmentRepository->findByProgramId($programId);
    }

    public function getEnrollmentProgressLogs(int $enrollmentId): array
    {
        return $this->progressLogRepository->findByEnrollmentId($enrollmentId);
    }
}
