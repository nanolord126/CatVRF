<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\VetGrooming\Domain\Entities\ProfessionalDevelopmentPlan;
use Modules\VetGrooming\Domain\Entities\TrainingCourse;
use Modules\VetGrooming\Domain\Entities\TrainingCompletion;
use Modules\VetGrooming\Domain\Entities\SkillMatrix;
use Modules\VetGrooming\Domain\Events\MasterLevelUp;
use Modules\VetGrooming\Domain\Repositories\ProfessionalDevelopmentPlanRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\TrainingCourseRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\TrainingCompletionRepositoryInterface;
use Modules\VetGrooming\Domain\Repositories\SkillMatrixRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\CacheManager;

/**
 * ProfessionalDevelopmentService — Сервис для профессионального развития мастеров
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 * - Audit logging
 */
final readonly class ProfessionalDevelopmentService
{
    use WithAuditLogging;

    public function __construct(
        private readonly ProfessionalDevelopmentPlanRepositoryInterface $planRepository,
        private readonly TrainingCourseRepositoryInterface $courseRepository,
        private readonly TrainingCompletionRepositoryInterface $completionRepository,
        private readonly SkillMatrixRepositoryInterface $skillMatrixRepository,
        private readonly AuditService $audit,
        private readonly CacheManager $cache,
    ) {}

    /**
     * Generate a professional development plan for a master (vet or groomer).
     */
    public function generateDevelopmentPlan(
        int $masterId,
        string $professionType,
        int $tenantId,
        string $currentLevel = 'junior',
        string $targetLevel = 'intermediate',
        ?float $effectivenessScore = null,
    ): ProfessionalDevelopmentPlan {
        $plan = ProfessionalDevelopmentPlan::create(
            tenantId: $tenantId,
            masterId: $masterId,
            professionType: $professionType,
            planStartDate: CarbonImmutable::now(),
            planEndDate: CarbonImmutable::now()->addMonths(12),
            currentLevel: $currentLevel,
            targetLevel: $targetLevel,
        );

        $plan = $plan->updateScores($effectivenessScore, null);

        // Get recommended courses based on profession type and level
        $courses = $this->getRecommendedCourses($professionType, $currentLevel, $targetLevel, $tenantId);
        $plan = new ProfessionalDevelopmentPlan(
            ...get_object_vars($plan),
            recommendedCourses: $courses,
            totalCoursesRequired: count($courses),
        );

        // Identify skill gaps
        $skillGaps = $this->identifySkillGaps($professionType, $currentLevel, $masterId, $tenantId);
        $plan = new ProfessionalDevelopmentPlan(
            ...get_object_vars($plan),
            skillGaps: $skillGaps,
        );

        $savedPlan = $this->planRepository->save($plan);
        $savedPlan = $savedPlan->activate();

        return $this->planRepository->save($savedPlan);
    }

    /**
     * Check mandatory courses compliance for a master.
     */
    public function checkMandatoryCoursesCompliance(int $masterId, string $professionType, int $tenantId): array
    {
        $mandatoryCourses = $this->courseRepository->findMandatoryByProfessionType($professionType, $tenantId);
        $completions = $this->completionRepository->findByMasterId($masterId);

        $complianceReport = [
            'compliant' => true,
            'missing_courses' => [],
            'expiring_soon' => [],
            'expired' => [],
        ];

        foreach ($mandatoryCourses as $course) {
            $completion = $this->findCompletionForCourse($completions, $course->id);

            if ($completion === null) {
                $complianceReport['compliant'] = false;
                $complianceReport['missing_courses'][] = [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'specialization' => $course->specialization,
                    'breed_restriction' => $course->mandatoryForBreeds,
                ];
            } elseif ($completion->isCertificateExpired()) {
                $complianceReport['compliant'] = false;
                $complianceReport['expired'][] = [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'expiry_date' => $completion->certificateExpiryDate?->toDateString(),
                ];
            } elseif ($completion->isCertificateExpiringWithin(30)) {
                $complianceReport['expiring_soon'][] = [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'expiry_date' => $completion->certificateExpiryDate?->toDateString(),
                ];
            }
        }

        return $complianceReport;
    }

    /**
     * Calculate development score for a master.
     */
    public function calculateDevelopmentScore(int $masterId, int $tenantId): float
    {
        $cacheKey = "development_score:{$masterId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(6), function () use ($masterId, $tenantId) {
            $plan = $this->planRepository->findByMasterId($masterId);
            $completions = $this->completionRepository->findByMasterId($masterId);
            $skills = $this->skillMatrixRepository->findByMasterId($masterId);

            if ($plan === null) {
                return 0.0;
            }

            // Components of development score
            $completionScore = $plan->completionPercentage * 0.4; // 40% weight
            $courseQualityScore = $this->calculateCourseQualityScore($completions) * 0.3; // 30% weight
            $skillProficiencyScore = $this->calculateSkillProficiencyScore($skills) * 0.2; // 20% weight
            $timelinessScore = $this->calculateTimelinessScore($plan) * 0.1; // 10% weight

            $totalScore = $completionScore + $courseQualityScore + $skillProficiencyScore + $timelinessScore;

            return round($totalScore, 2);
        });
    }

    /**
     * Update development plan progress.
     */
    public function updatePlanProgress(int $planId): ProfessionalDevelopmentPlan
    {
        $plan = $this->planRepository->findById($planId);
        if ($plan === null) {
            throw new \InvalidArgumentException("Plan not found: {$planId}");
        }

        $completions = $this->completionRepository->findByDevelopmentPlanId($planId);
        $coursesCompleted = count($completions);
        $totalCourses = $plan->totalCoursesRequired;

        $updatedPlan = $plan->updateProgress($coursesCompleted, $totalCourses);

        // Check if plan is complete
        if ($updatedPlan->completionPercentage >= 100) {
            $updatedPlan = $updatedPlan->complete();

            // Check for level up
            if ($updatedPlan->hasLevelUp()) {
                $this->handleLevelUp($updatedPlan);
            }
        }

        // Update development score
        $developmentScore = $this->calculateDevelopmentScore($plan->masterId, $plan->tenantId);
        $updatedPlan = $updatedPlan->updateScores($updatedPlan->effectivenessScore, $developmentScore);

        return $this->planRepository->save($updatedPlan);
    }

    /**
     * Add training completion to a development plan.
     */
    public function addTrainingCompletion(
        int $masterId,
        int $courseId,
        ?int $developmentPlanId = null,
        ?float $score = null,
        ?int $durationHours = null,
        ?string $certificateFile = null,
        ?CarbonImmutable $certificateExpiryDate = null,
    ): TrainingCompletion {
        $completion = TrainingCompletion::create(
            tenantId: 0, // Will be set from plan or master
            masterId: $masterId,
            courseId: $courseId,
            developmentPlanId: $developmentPlanId,
            durationActualHours: $durationHours,
            score: $score,
            certificateFile: $certificateFile,
            certificateExpiryDate: $certificateExpiryDate,
        );

        $savedCompletion = $this->completionRepository->save($completion);

        // Update plan progress if linked
        if ($developmentPlanId !== null) {
            $this->updatePlanProgress($developmentPlanId);
        }

        return $savedCompletion;
    }

    /**
     * Update or create a skill matrix entry.
     */
    public function updateSkill(
        int $masterId,
        string $skill,
        string $proficiencyLevel,
        ?string $skillCategory = null,
        ?int $practiceHours = null,
        ?array $evidence = null,
        ?int $tenantId = null,
    ): SkillMatrix {
        $existingSkill = $this->skillMatrixRepository->findByMasterIdAndSkill($masterId, $skill);

        if ($existingSkill !== null) {
            $updatedSkill = $existingSkill->updateProficiency($proficiencyLevel);
            if ($practiceHours !== null) {
                $updatedSkill = $updatedSkill->addPracticeHours($practiceHours);
            }
            if ($evidence !== null) {
                $updatedSkill = $updatedSkill->addEvidence($evidence);
            }
            return $this->skillMatrixRepository->save($updatedSkill);
        }

        $newSkill = SkillMatrix::create(
            tenantId: $tenantId ?? 0,
            masterId: $masterId,
            skill: $skill,
            proficiencyLevel: $proficiencyLevel,
            skillCategory: $skillCategory,
        );

        if ($practiceHours !== null) {
            $newSkill = $newSkill->addPracticeHours($practiceHours);
        }

        return $this->skillMatrixRepository->save($newSkill);
    }

    /**
     * Get upcoming courses for a master.
     */
    public function getUpcomingCourses(int $masterId, int $tenantId, int $days = 30): array
    {
        $plan = $this->planRepository->findByMasterId($masterId);
        if ($plan === null || $plan->recommendedCourses === null) {
            return [];
        }

        $completedCourseIds = array_map(
            fn ($c) => $c->courseId,
            $this->completionRepository->findByMasterId($masterId),
        );

        $upcoming = [];
        foreach ($plan->recommendedCourses as $courseId) {
            if (! in_array($courseId, $completedCourseIds, true)) {
                $course = $this->courseRepository->findById($courseId);
                if ($course !== null) {
                    $upcoming[] = $course;
                }
            }
        }

        return $upcoming;
    }

    /**
     * Get recommended courses based on profession type and level.
     */
    private function getRecommendedCourses(string $professionType, string $currentLevel, string $targetLevel, int $tenantId): array
    {
        $courses = $this->courseRepository->findByProfessionType($professionType, $tenantId);
        $recommended = [];

        foreach ($courses as $course) {
            if ($course->isActive && $course->requiresLevel($currentLevel)) {
                $recommended[] = $course->id;
            }
        }

        return $recommended;
    }

    /**
     * Identify skill gaps for a master.
     */
    private function identifySkillGaps(string $professionType, string $currentLevel, int $masterId, int $tenantId): string
    {
        $requiredSkills = $this->getRequiredSkillsForLevel($professionType, $currentLevel);
        $currentSkills = $this->skillMatrixRepository->findByMasterId($masterId);

        $currentSkillNames = array_map(fn ($s) => $s->skill, $currentSkills);
        $gaps = array_diff($requiredSkills, $currentSkillNames);

        return empty($gaps) ? null : implode(', ', $gaps);
    }

    /**
     * Get required skills for a given level.
     */
    private function getRequiredSkillsForLevel(string $professionType, string $level): array
    {
        $skillsMap = [
            'vet' => [
                'junior' => ['Basic examination', 'Vaccination protocols', 'Medical history taking'],
                'intermediate' => ['Diagnostic imaging', 'Blood work analysis', 'Common surgeries'],
                'senior' => ['Complex surgery', 'Emergency care', 'Specialized diagnostics'],
                'expert' => ['Advanced procedures', 'Teaching/mentoring', 'Research'],
                'master' => ['Innovation', 'Leadership', 'Strategic planning'],
            ],
            'groomer' => [
                'junior' => ['Basic bathing', 'Nail trimming', 'Ear cleaning'],
                'intermediate' => ['Breed-specific cuts', 'Dematting', 'Spa treatments'],
                'senior' => ['Show grooming', 'Aggressive handling', 'Creative styling'],
                'expert' => ['Competition preparation', 'Advanced techniques', 'Mentoring'],
                'master' => ['Industry leadership', 'Education', 'Innovation'],
            ],
        ];

        return $skillsMap[$professionType][$level] ?? [];
    }

    /**
     * Calculate course quality score based on completion grades.
     */
    private function calculateCourseQualityScore(array $completions): float
    {
        if (empty($completions)) {
            return 0.0;
        }

        $totalScore = 0;
        $count = 0;

        foreach ($completions as $completion) {
            if ($completion->score !== null) {
                $totalScore += $completion->score;
                $count++;
            }
        }

        if ($count === 0) {
            return 50.0; // Neutral score if no graded courses
        }

        return $totalScore / $count;
    }

    /**
     * Calculate skill proficiency score.
     */
    private function calculateSkillProficiencyScore(array $skills): float
    {
        if (empty($skills)) {
            return 0.0;
        }

        $proficiencyValues = [
            'beginner' => 25,
            'intermediate' => 50,
            'advanced' => 75,
            'expert' => 100,
        ];

        $totalScore = 0;
        foreach ($skills as $skill) {
            $totalScore += $proficiencyValues[$skill->proficiencyLevel] ?? 0;
        }

        return $totalScore / count($skills);
    }

    /**
     * Calculate timeliness score based on plan progress vs expected.
     */
    private function calculateTimelinessScore(ProfessionalDevelopmentPlan $plan): float
    {
        if ($plan->isComplete()) {
            return 100.0;
        }

        if ($plan->isOverdue()) {
            return 0.0;
        }

        $elapsedDays = $plan->planStartDate->diffInDays(CarbonImmutable::now());
        $totalDays = $plan->planStartDate->diffInDays($plan->planEndDate);

        if ($totalDays === 0) {
            return 50.0;
        }

        $expectedProgress = ($elapsedDays / $totalDays) * 100;
        $actualProgress = $plan->completionPercentage;

        if ($actualProgress >= $expectedProgress) {
            return 100.0;
        }

        return max(0, $actualProgress / $expectedProgress * 100);
    }

    /**
     * Handle level up event.
     */
    private function handleLevelUp(ProfessionalDevelopmentPlan $plan): void
    {
        $levels = ['junior', 'intermediate', 'senior', 'expert', 'master'];
        $previousLevel = $plan->currentLevel;
        $newLevel = $plan->targetLevel;

        Event::dispatch(new MasterLevelUp(
            developmentPlan: $plan,
            previousLevel: $previousLevel,
            newLevel: $newLevel,
            masterId: $plan->masterId,
            tenantId: $plan->tenantId,
        ));
    }

    /**
     * Find completion for a specific course.
     */
    private function findCompletionForCourse(array $completions, int $courseId): ?TrainingCompletion
    {
        foreach ($completions as $completion) {
            if ($completion->courseId === $courseId) {
                return $completion;
            }
        }

        return null;
    }
}
