<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Fitness\Domain\Entities\CertificationTestResult;
use Modules\Fitness\Domain\Entities\TrainerCertification;
use Modules\Fitness\Domain\Entities\TrainerDevelopmentPlan;
use Modules\Fitness\Domain\Entities\TrainerSpecialization;
use Modules\Fitness\Infrastructure\Models\CertificationTestResultModel;
use Modules\Fitness\Infrastructure\Models\TrainerCertificationModel;
use Modules\Fitness\Infrastructure\Models\TrainerDevelopmentPlanModel;
use Modules\Fitness\Infrastructure\Models\TrainerModel;
use Modules\Fitness\Infrastructure\Models\TrainerSpecializationModel;

final readonly class TrainerCertificationService
{
    public function verifyCertification(int $certificationId, int $verifiedBy): TrainerCertification
    {
        return DB::transaction(function () use ($certificationId, $verifiedBy) {
            $model = TrainerCertificationModel::findOrFail($certificationId);
            $certification = $model->toDomain()->verify($verifiedBy);

            $updatedModel = TrainerCertificationModel::fromDomain($certification);
            $updatedModel->save();

            Log::info('Certification verified', [
                'certification_id' => $certificationId,
                'verified_by' => $verifiedBy,
                'trainer_id' => $certification->trainerId,
            ]);

            return $updatedModel->toDomain();
        });
    }

    public function checkExpiringCertificates(): array
    {
        $expiringSoon = TrainerCertificationModel::where('status', 'active')
            ->where('expiry_date', '<=', CarbonImmutable::now()->addDays(30))
            ->where('expiry_date', '>', CarbonImmutable::now())
            ->with('trainer')
            ->get();

        $expired = TrainerCertificationModel::where('status', 'active')
            ->where('expiry_date', '<=', CarbonImmutable::now())
            ->with('trainer')
            ->get();

        $results = [
            'expiring_soon' => $expiringSoon->map(fn ($m) => $m->toDomain())->toArray(),
            'expired' => [],
        ];

        // Mark expired certificates
        foreach ($expired as $model) {
            $certification = $model->toDomain()->markAsExpired();
            $updatedModel = TrainerCertificationModel::fromDomain($certification);
            $updatedModel->save();

            // Put trainer on hold if critical certification expired
            $this->handleExpiredCertification($certification);

            $results['expired'][] = $updatedModel->toDomain();
        }

        return $results;
    }

    public function generateDevelopmentPlan(int $trainerId): TrainerDevelopmentPlan
    {
        $trainer = TrainerModel::findOrFail($trainerId);
        $certifications = TrainerCertificationModel::where('trainer_id', $trainerId)
            ->where('status', 'active')
            ->get();

        $requiredCourses = [];
        $goal = 'Maintain current certification level';

        // Check for required courses based on current level
        $currentLevel = $trainer->qualification_level ?? 'junior';
        $nextLevel = $this->getNextLevel($currentLevel);

        if ($nextLevel) {
            $goal = "Advance to {$nextLevel} level";
            $requiredCourses = $this->getRequiredCoursesForLevel($nextLevel);
        }

        $targetDate = CarbonImmutable::now()->addMonths(6);

        $plan = TrainerDevelopmentPlan::create(
            tenantId: $trainer->tenant_id,
            trainerId: $trainerId,
            goal: $goal,
            targetDate: $targetDate,
            description: "Personal development plan to advance trainer skills and certifications",
            businessGroupId: $trainer->business_group_id,
        );

        $plan = new TrainerDevelopmentPlan(
            ...get_object_vars($plan),
            requiredCourses: $requiredCourses,
        );

        $model = TrainerDevelopmentPlanModel::fromDomain($plan);
        $model->save();

        return $model->toDomain();
    }

    public function assignSpecialization(
        int $trainerId,
        string $specialization,
        string $level = 'basic',
        ?int $certificationId = null,
    ): TrainerSpecialization {
        $trainer = TrainerModel::findOrFail($trainerId);

        $specializationEntity = TrainerSpecialization::create(
            tenantId: $trainer->tenant_id,
            trainerId: $trainerId,
            specialization: $specialization,
            level: $level,
            certificationId: $certificationId,
            businessGroupId: $trainer->business_group_id,
        );

        $model = TrainerSpecializationModel::fromDomain($specializationEntity);
        $model->save();

        Log::info('Specialization assigned', [
            'trainer_id' => $trainerId,
            'specialization' => $specialization,
            'level' => $level,
        ]);

        return $model->toDomain();
    }

    public function canTrainerLeadSession(int $trainerId, string $sessionType): bool
    {
        $trainer = TrainerModel::findOrFail($trainerId);

        // Check if trainer is on hold
        if ($trainer->is_on_hold ?? false) {
            return false;
        }

        // Check for required specializations based on session type
        $requiredSpecializations = $this->getRequiredSpecializationsForSession($sessionType);

        if (empty($requiredSpecializations)) {
            return true;
        }

        $trainerSpecializations = TrainerSpecializationModel::where('trainer_id', $trainerId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', CarbonImmutable::now());
            })
            ->pluck('specialization')
            ->toArray();

        foreach ($requiredSpecializations as $required) {
            if (!in_array($required, $trainerSpecializations, true)) {
                return false;
            }
        }

        return true;
    }

    public function processTestResult(
        int $testResultId,
        int $theoryScore,
        int $practiceScore,
        int $evaluatorId,
        ?string $feedback = null,
    ): CertificationTestResult {
        return DB::transaction(function () use ($testResultId, $theoryScore, $practiceScore, $evaluatorId, $feedback) {
            $model = CertificationTestResultModel::findOrFail($testResultId);
            $testResult = $model->toDomain()->evaluate($theoryScore, $practiceScore, $evaluatorId, $feedback);

            $updatedModel = CertificationTestResultModel::fromDomain($testResult);
            $updatedModel->save();

            // If test passed and is linked to a certification, verify the certification
            if ($testResult->passed && $testResult->certificationId) {
                $this->verifyCertification($testResult->certificationId, $evaluatorId);

                // Update trainer qualification level if applicable
                $this->updateTrainerQualificationLevel($testResult->trainerId);
            }

            return $updatedModel->toDomain();
        });
    }

    public function getTrainersNeedingAttention(): array
    {
        $trainersOnHold = TrainerModel::where('is_on_hold', true)
            ->with('certifications')
            ->get();

        $trainersWithExpiringCerts = TrainerCertificationModel::where('status', 'active')
            ->where('expiry_date', '<=', CarbonImmutable::now()->addDays(60))
            ->where('expiry_date', '>', CarbonImmutable::now())
            ->with('trainer')
            ->get()
            ->pluck('trainer')
            ->unique('id')
            ->values();

        return [
            'on_hold' => $trainersOnHold->toArray(),
            'expiring_soon' => $trainersWithExpiringCerts->toArray(),
        ];
    }

    private function handleExpiredCertification(TrainerCertification $certification): void
    {
        $trainer = TrainerModel::find($certification->trainerId);
        if (!$trainer) {
            return;
        }

        // Check if this is a critical certification
        $isCritical = $this->isCriticalCertification($certification->name);

        if ($isCritical) {
            $trainer->is_on_hold = true;
            $trainer->on_hold_since = CarbonImmutable::now();
            $trainer->on_hold_reason = "Critical certification expired: {$certification->name}";
            $trainer->save();

            Log::warning('Trainer placed on hold due to expired certification', [
                'trainer_id' => $certification->trainerId,
                'certification' => $certification->name,
            ]);
        }
    }

    private function isCriticalCertification(string $certificationName): bool
    {
        $criticalCertifications = [
            'Prenatal Fitness Specialist',
            'Kids Fitness Instructor',
            'Senior Fitness Specialist',
            'CPR/AED Certification',
            'First Aid Certification',
        ];

        return in_array($certificationName, $criticalCertifications, true);
    }

    private function getNextLevel(string $currentLevel): ?string
    {
        $levels = ['junior', 'certified', 'senior', 'master'];
        $currentIndex = array_search($currentLevel, $levels, true);

        if ($currentIndex === false || $currentIndex === count($levels) - 1) {
            return null;
        }

        return $levels[$currentIndex + 1];
    }

    private function getRequiredCoursesForLevel(string $level): array
    {
        $courses = [
            'certified' => [
                ['name' => 'Advanced Anatomy & Physiology', 'type' => 'internal', 'due_date' => CarbonImmutable::now()->addMonths(3)->toDateString()],
                ['name' => 'Exercise Programming', 'type' => 'internal', 'due_date' => CarbonImmutable::now()->addMonths(3)->toDateString()],
            ],
            'senior' => [
                ['name' => 'Advanced Training Techniques', 'type' => 'internal', 'due_date' => CarbonImmutable::now()->addMonths(4)->toDateString()],
                ['name' => 'Client Assessment & Progression', 'type' => 'internal', 'due_date' => CarbonImmutable::now()->addMonths(4)->toDateString()],
            ],
            'master' => [
                ['name' => 'Mentorship Training', 'type' => 'internal', 'due_date' => CarbonImmutable::now()->addMonths(6)->toDateString()],
                ['name' => 'Program Design Certification', 'type' => 'external', 'due_date' => CarbonImmutable::now()->addMonths(6)->toDateString()],
            ],
        ];

        return $courses[$level] ?? [];
    }

    private function getRequiredSpecializationsForSession(string $sessionType): array
    {
        $requirements = [
            'prenatal' => ['Prenatal'],
            'kids' => ['Kids'],
            'senior' => ['Senior'],
            'rehabilitation' => ['Rehabilitation'],
        ];

        return $requirements[$sessionType] ?? [];
    }

    private function updateTrainerQualificationLevel(int $trainerId): void
    {
        $trainer = TrainerModel::find($trainerId);
        if (!$trainer) {
            return;
        }

        $certifications = TrainerCertificationModel::where('trainer_id', $trainerId)
            ->where('status', 'active')
            ->where('is_verified', true)
            ->count();

        $specializations = TrainerSpecializationModel::where('trainer_id', $trainerId)
            ->where('is_active', true)
            ->where('level', 'advanced')
            ->count();

        // Simple logic for qualification level
        $newLevel = 'junior';
        if ($certifications >= 2 && $specializations >= 1) {
            $newLevel = 'certified';
        }
        if ($certifications >= 4 && $specializations >= 2) {
            $newLevel = 'senior';
        }
        if ($certifications >= 6 && $specializations >= 3) {
            $newLevel = 'master';
        }

        if ($newLevel !== $trainer->qualification_level) {
            $trainer->qualification_level = $newLevel;
            $trainer->save();

            Log::info('Trainer qualification level updated', [
                'trainer_id' => $trainerId,
                'old_level' => $trainer->qualification_level,
                'new_level' => $newLevel,
            ]);
        }
    }
}
