<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services\Staff;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Modules\CatCRM\Application\DTOs\Staff\CreateEmployeeDTO;
use Modules\CatCRM\Application\DTOs\Staff\UpdateEmployeeDTO;
use Modules\CatCRM\Application\Services\Staff\Gamification\BadgeService;
use Modules\CatCRM\Application\Services\Staff\Gamification\AchievementService;
use Modules\CatCRM\Application\Services\Staff\Gamification\LeaderboardService;
use Modules\CatCRM\Application\Services\Staff\Gamification\ChallengeService;
use Modules\CatCRM\Application\Services\Staff\Gamification\LevelProgressService;
use Modules\CatCRM\Application\Services\Staff\Social\PeerReviewService;
use Modules\CatCRM\Application\Services\Staff\Social\MentoringService;
use Modules\CatCRM\Application\Services\Staff\Training\TrainingCourseService;
use Modules\CatCRM\Application\Services\Staff\Training\CertificationService;
use Modules\CatCRM\Application\Services\Staff\Training\PDPService;
use Modules\CatCRM\Application\Services\Staff\Schedule\ShiftScheduleService;
use Modules\CatCRM\Application\Services\Staff\Schedule\LeaveManagementService;
use Modules\CatCRM\Application\Services\Staff\Schedule\SubstituteService;
use Modules\CatCRM\Application\Services\Staff\Wellness\WorkLifeBalanceService;
use Modules\CatCRM\Application\Services\Staff\Wellness\BreakReminderService;
use Modules\CatCRM\Application\Services\Staff\Wellness\FitnessTrackerService;
use Modules\CatCRM\Application\Services\Staff\Analytics\TeamDynamicsAnalyzerService;
use Modules\CatCRM\Application\Services\Staff\Analytics\ScheduleOptimizationService;
use Modules\CatCRM\Application\Services\Staff\Analytics\HiringForecastService;
use Modules\CatCRM\Application\Services\Staff\Communication\InternalChatService;
use Modules\CatCRM\Application\Services\Staff\Communication\AnnouncementService;
use Modules\CatCRM\Application\Services\Staff\Communication\ProjectDiscussionService;
use Modules\CatCRM\Application\Services\Staff\Communication\SlackTeamsIntegrationService;
use Illuminate\Support\Facades\DB;

/**
 * CRMStaffIntegrationService — Основной сервис интеграции Staff HRM
 * 
 * Объединяет все функции HRM:
 * - AI/ML (анализ производительности, предсказание выгорания, рекомендации)
 * - Геймификация (бейджи, достижения, лидерборды, челленджи, уровни)
 * - Социальные функции (peer review, наставничество, соцсеть, благодарности)
 * - Обучение (курсы, сертификации, PDP, навыки)
 * - График (смены, отпуска, чек-ин/чек-аут)
 * - Благополучие (work-life balance, перерывы, стресс, фитнес)
 * - Аналитика (командная динамика, оптимизация, прогноз, KPI)
 * - Коммуникация (чат, объявления, проекты, Slack/Teams)
 * 
 * Following CatVRF rules:
 * - WithAuditLogging trait
 * - Clean Architecture + DDD
 * - Async LLM calls
 * - Medical compliance (PII anonymization)
 * - Cache with tags
 */
final class CRMStaffIntegrationService
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
        public StaffPerformanceAnalyzerService $performanceAnalyzer,
        public BurnoutPredictionService $burnoutPrediction,
        public SkillMatchingService $skillMatching,
        public TrainingRecommendationService $trainingRecommendation,
        public BadgeService $badgeService,
        public CertificationService $certificationService,
        public PDPService $pdpService,
        public AchievementService $achievementService,
        public LeaderboardService $leaderboardService,
        public SubstituteService $substituteService,
        public ChallengeService $challengeService,
        public BreakReminderService $breakReminderService,
        public FitnessTrackerService $fitnessTrackerService,
        public LevelProgressService $levelProgressService,
        public ScheduleOptimizationService $scheduleOptimization,
        public HiringForecastService $hiringForecast,
        public PeerReviewService $peerReviewService,
        public AnnouncementService $announcementService,
        public ProjectDiscussionService $projectDiscussionService,
        public SlackTeamsIntegrationService $slackTeamsIntegration,
        public MentoringService $mentoringService,
        public TrainingCourseService $trainingCourseService,
        public ShiftScheduleService $shiftScheduleService,
        public LeaveManagementService $leaveManagementService,
        public WorkLifeBalanceService $workLifeBalanceService,
        public TeamDynamicsAnalyzerService $teamDynamicsAnalyzer,
        public InternalChatService $internalChatService,
    ) {}

    /**
     * Создать сотрудника
     */
    public function createEmployee(CreateEmployeeDTO $dto, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($dto, $correlationId, $userId) {
            // TODO: Create employee via repository
            $employeeId = 1; // Placeholder

            $this->logCreated(
                entityType: 'employee',
                entityId: $employeeId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $dto->tenantId,
                    'user_id' => $dto->userId,
                ],
                userId: $userId,
                tenantId: $dto->tenantId
            );

            return [
                'employee_id' => $employeeId,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Обновить сотрудника
     */
    public function updateEmployee(int $employeeId, UpdateEmployeeDTO $dto, int $tenantId, ?int $userId = null): bool
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($employeeId, $dto, $tenantId, $correlationId, $userId) {
            // TODO: Update employee via repository
            $changes = $dto->toArray();

            $this->logUpdated(
                entityType: 'employee',
                entityId: $employeeId,
                changes: $changes,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return true;
        });
    }

    /**
     * Получить полный профиль сотрудника со всеми метриками
     */
    public function getEmployeeProfile(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        $this->logAction(
            action: 'employee_profile_viewed',
            entityType: 'employee',
            entityId: $employeeId,
            context: ['tenant_id' => $tenantId],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'employee' => [], // TODO: Fetch from repository
            'performance_analysis' => $this->performanceAnalyzer->getPerformanceAnalysis($tenantId, $employeeId),
            'burnout_prediction' => $this->burnoutPrediction->getBurnoutPrediction($tenantId, $employeeId),
            'achievements' => $this->achievementService->getEmployeeAchievements($tenantId, $employeeId),
            'progress' => $this->levelProgressService->getEmployeeProgress($tenantId, $employeeId),
            'rank' => $this->leaderboardService->getEmployeeRank($tenantId, $employeeId),
        ];
    }

    // Делегирующие методы для всех подсистем

    public function analyzePerformance(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->performanceAnalyzer->analyzePerformance($tenantId, $employeeId, $userId);
    }

    public function predictBurnoutRisk(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->burnoutPrediction->predictBurnoutRisk($tenantId, $employeeId, $userId);
    }

    public function matchEmployeesToTask(int $tenantId, array $skills, string $category, ?int $userId = null): array
    {
        return $this->skillMatching->matchEmployeesToTask($tenantId, $skills, $category, $userId);
    }

    public function getTrainingRecommendations(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->trainingRecommendation->getTrainingRecommendations($tenantId, $employeeId, $userId);
    }

    // ========== Layer 4: Gamification Services ==========

    public function awardBadge(int $tenantId, int $employeeId, string $badgeId, ?int $userId = null): array
    {
        return $this->badgeService->awardBadge($tenantId, $employeeId, $badgeId, $userId);
    }

    public function unlockAchievement(int $tenantId, int $employeeId, string $achievementId, ?int $userId = null): array
    {
        return $this->achievementService->unlockAchievement($tenantId, $employeeId, $achievementId, $userId);
    }

    public function getLeaderboard(int $tenantId, string $period = 'weekly', ?int $userId = null): array
    {
        return $this->leaderboardService->getLeaderboard($tenantId, $period, $userId);
    }

    public function getEmployeeRank(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->leaderboardService->getEmployeeRank($tenantId, $employeeId, $userId);
    }

    public function createChallenge(int $tenantId, array $challengeData, ?int $userId = null): array
    {
        return $this->challengeService->createChallenge($tenantId, $challengeData, $userId);
    }

    public function getEmployeeProgress(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->levelProgressService->getEmployeeProgress($tenantId, $employeeId, $userId);
    }

    // ========== Layer 4: Social Services ==========

    public function submitPeerReview(int $tenantId, int $reviewerId, int $revieweeId, array $reviewData, ?int $userId = null): array
    {
        return $this->peerReviewService->submitPeerReview($tenantId, $reviewerId, $revieweeId, $reviewData, $userId);
    }

    public function createMentorship(int $tenantId, int $mentorId, int $menteeId, array $mentorshipData, ?int $userId = null): array
    {
        return $this->mentoringService->createMentorship($tenantId, $mentorId, $menteeId, $mentorshipData, $userId);
    }

    public function sendGratitude(int $tenantId, int $senderId, int $receiverId, string $message, ?int $userId = null): array
    {
        // Using GratitudeService if exists, otherwise log action
        $this->logAction(
            action: 'gratitude_sent',
            entityType: 'employee',
            entityId: $receiverId,
            context: [
                'tenant_id' => $tenantId,
                'sender_id' => $senderId,
                'message' => $message,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return ['success' => true];
    }

    // ========== Layer 4: Training Services ==========

    public function createTrainingCourse(int $tenantId, array $courseData, ?int $userId = null): array
    {
        return $this->trainingCourseService->createTrainingCourse($tenantId, $courseData, $userId);
    }

    public function enrollEmployeeInCourse(int $tenantId, int $employeeId, int $courseId, ?int $userId = null): array
    {
        return $this->trainingCourseService->enrollEmployee($tenantId, $employeeId, $courseId, $userId);
    }

    public function createCertification(int $tenantId, int $employeeId, array $certificationData, ?int $userId = null): array
    {
        return $this->certificationService->createCertification($tenantId, $employeeId, $certificationData, $userId);
    }

    public function createPDP(int $tenantId, int $employeeId, array $pdpData, ?int $userId = null): array
    {
        return $this->pdpService->createPDP($tenantId, $employeeId, $pdpData, $userId);
    }

    // ========== Layer 4: Schedule Services ==========

    public function createShift(int $tenantId, array $shiftData, ?int $userId = null): array
    {
        return $this->shiftScheduleService->createShift($tenantId, $shiftData, $userId);
    }

    public function assignEmployeeToShift(int $tenantId, int $shiftId, int $employeeId, ?int $userId = null): array
    {
        return $this->shiftScheduleService->assignEmployee($tenantId, $shiftId, $employeeId, $userId);
    }

    public function clockIn(int $tenantId, int $employeeId, array $locationData, ?int $userId = null): array
    {
        return $this->shiftScheduleService->clockIn($tenantId, $employeeId, $locationData, $userId);
    }

    public function clockOut(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->shiftScheduleService->clockOut($tenantId, $employeeId, $userId);
    }

    public function requestLeave(int $tenantId, int $employeeId, array $leaveData, ?int $userId = null): array
    {
        return $this->leaveManagementService->requestLeave($tenantId, $employeeId, $leaveData, $userId);
    }

    public function approveLeave(int $tenantId, int $leaveId, int $approverId, ?int $userId = null): array
    {
        return $this->leaveManagementService->approveLeave($tenantId, $leaveId, $approverId, $userId);
    }

    public function findSubstitute(int $tenantId, int $shiftId, array $criteria, ?int $userId = null): array
    {
        return $this->substituteService->findSubstitute($tenantId, $shiftId, $criteria, $userId);
    }

    public function assignSubstitute(int $tenantId, int $shiftId, int $substituteId, ?int $userId = null): array
    {
        return $this->substituteService->assignSubstitute($tenantId, $shiftId, $substituteId, $userId);
    }

    // ========== Layer 4: Wellness Services ==========

    public function recordWellnessMetrics(int $tenantId, int $employeeId, array $metrics, ?int $userId = null): array
    {
        return $this->workLifeBalanceService->recordMetrics($tenantId, $employeeId, $metrics, $userId);
    }

    public function getWorkLifeBalanceScore(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        return $this->workLifeBalanceService->getBalanceScore($tenantId, $employeeId, $userId);
    }

    public function scheduleBreakReminder(int $tenantId, int $employeeId, array $schedule, ?int $userId = null): array
    {
        return $this->breakReminderService->scheduleReminder($tenantId, $employeeId, $schedule, $userId);
    }

    public function syncFitnessTracker(int $tenantId, int $employeeId, array $trackerData, ?int $userId = null): array
    {
        return $this->fitnessTrackerService->syncData($tenantId, $employeeId, $trackerData, $userId);
    }

    // ========== Layer 4: Analytics Services ==========

    public function analyzeTeamDynamics(int $tenantId, int $teamId, ?int $userId = null): array
    {
        return $this->teamDynamicsAnalyzer->analyzeTeam($tenantId, $teamId, $userId);
    }

    public function optimizeSchedule(int $tenantId, array $constraints, ?int $userId = null): array
    {
        return $this->scheduleOptimization->optimize($tenantId, $constraints, $userId);
    }

    public function generateHiringForecast(int $tenantId, int $horizonMonths, ?int $userId = null): array
    {
        return $this->hiringForecast->generateForecast($tenantId, $horizonMonths, $userId);
    }

    // ========== Layer 4: Communication Services ==========

    public function sendInternalMessage(int $tenantId, int $senderId, int $recipientId, array $messageData, ?int $userId = null): array
    {
        return $this->internalChatService->sendMessage($tenantId, $senderId, $recipientId, $messageData, $userId);
    }

    public function createAnnouncement(int $tenantId, int $authorId, array $announcementData, ?int $userId = null): array
    {
        return $this->announcementService->createAnnouncement($tenantId, $authorId, $announcementData, $userId);
    }

    public function createProjectDiscussion(int $tenantId, int $projectId, array $discussionData, ?int $userId = null): array
    {
        return $this->projectDiscussionService->createDiscussion($tenantId, $projectId, $discussionData, $userId);
    }

    public function syncWithSlack(int $tenantId, array $slackData, ?int $userId = null): array
    {
        return $this->slackTeamsIntegration->syncSlack($tenantId, $slackData, $userId);
    }

    public function syncWithTeams(int $tenantId, array $teamsData, ?int $userId = null): array
    {
        return $this->slackTeamsIntegration->syncTeams($tenantId, $teamsData, $userId);
    }

    // ========== Layer 3: Orchestration Methods ==========

    /**
     * Полная онбординг-процедура для нового сотрудника
     */
    public function onboardNewEmployee(int $tenantId, int $employeeId, array $onboardingData, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        return DB::transaction(function () use ($tenantId, $employeeId, $onboardingData, $correlationId, $userId) {
            // 1. Create initial shift schedule
            $this->createShift($tenantId, [
                'employee_id' => $employeeId,
                'start_date' => $onboardingData['start_date'] ?? now(),
            ], $userId);

            // 2. Assign mentor if specified
            if (!empty($onboardingData['mentor_id'])) {
                $this->createMentorship($tenantId, $onboardingData['mentor_id'], $employeeId, [
                    'start_date' => now(),
                    'status' => 'active',
                ], $userId);
            }

            // 3. Create PDP
            $this->createPDP($tenantId, $employeeId, [
                'goals' => $onboardingData['goals'] ?? [],
                'timeline' => $onboardingData['timeline'] ?? '90_days',
            ], $userId);

            // 4. Enroll in required training courses
            if (!empty($onboardingData['required_courses'])) {
                foreach ($onboardingData['required_courses'] as $courseId) {
                    $this->enrollEmployeeInCourse($tenantId, $employeeId, $courseId, $userId);
                }
            }

            $this->logAction(
                action: 'employee_onboarded',
                entityType: 'employee',
                entityId: $employeeId,
                context: [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'onboarding_data' => $onboardingData,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            return [
                'employee_id' => $employeeId,
                'correlation_id' => $correlationId,
                'status' => 'onboarded',
            ];
        });
    }

    /**
     * Еженедельный анализ сотрудника (performance + burnout + wellness)
     */
    public function performWeeklyEmployeeAnalysis(int $tenantId, int $employeeId, ?int $userId = null): array
    {
        $correlationId = $this->generateCorrelationId();

        $performance = $this->analyzePerformance($tenantId, $employeeId, $userId);
        $burnout = $this->predictBurnoutRisk($tenantId, $employeeId, $userId);
        $wellness = $this->getWorkLifeBalanceScore($tenantId, $employeeId, $userId);

        // Auto-trigger actions based on analysis
        if ($burnout['risk_level'] === 'high') {
            $this->scheduleBreakReminder($tenantId, $employeeId, [
                'frequency' => 'daily',
                'duration' => 30,
            ], $userId);
        }

        $this->logAction(
            action: 'weekly_analysis_performed',
            entityType: 'employee',
            entityId: $employeeId,
            context: [
                'correlation_id' => $correlationId,
                'tenant_id' => $tenantId,
                'performance_score' => $performance['score'] ?? null,
                'burnout_risk' => $burnout['risk_level'] ?? null,
                'wellness_score' => $wellness['score'] ?? null,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'correlation_id' => $correlationId,
            'performance' => $performance,
            'burnout' => $burnout,
            'wellness' => $wellness,
        ];
    }

    /**
     * Получить полный dashboard для менеджера
     */
    public function getManagerDashboard(int $tenantId, int $managerId, array $filters = [], ?int $userId = null): array
    {
        $this->logAction(
            action: 'manager_dashboard_viewed',
            entityType: 'team',
            entityId: $managerId,
            context: [
                'tenant_id' => $tenantId,
                'filters' => $filters,
            ],
            userId: $userId,
            tenantId: $tenantId
        );

        return [
            'team_dynamics' => $this->analyzeTeamDynamics($tenantId, $filters['team_id'] ?? null, $userId),
            'leaderboard' => $this->getLeaderboard($tenantId, $filters['period'] ?? 'weekly', $userId),
            'hiring_forecast' => $this->generateHiringForecast($tenantId, $filters['horizon_months'] ?? 6, $userId),
            'schedule_optimization' => $this->optimizeSchedule($tenantId, $filters['constraints'] ?? [], $userId),
        ];
    }
}
