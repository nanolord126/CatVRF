<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffSkill;
use App\Domains\Staff\Domain\Entities\StaffPrediction;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffAILearningRecommendationService — AI-рекомендации по обучению и развитию.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Анализирует навыки, производительность, карьерные цели
 * для генерации персонализированных рекомендаций по обучению.
 */
final class StaffAILearningRecommendationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Запускает генерацию рекомендаций по обучению (асинхронно).
     */
    public function generateLearningRecommendations(int $staffId): void
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_learning_recommendations',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        \App\Jobs\Staff\GenerateLearningRecommendationsJob::dispatch($staffId, auth()->id());

        $this->logAction('learning_recommendations_requested', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
        ]);
    }

    /**
     * Сохраняет рекомендации по обучению (вызывается из Job).
     */
    public function saveLearningRecommendations(
        int $staffId,
        array $recommendations,
    ): StaffPrediction {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_learning_save',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        $prediction = StaffPrediction::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'prediction_type' => 'skill_gap',
            'confidence_score' => $recommendations['confidence_score'] ?? 0.75,
            'risk_level' => 'low',
            'prediction_data' => [
                'recommended_courses' => $recommendations['courses'] ?? [],
                'skill_gaps' => $recommendations['skill_gaps'] ?? [],
                'career_path' => $recommendations['career_path'] ?? [],
            ],
            'factors' => $recommendations['factors'] ?? [],
            'recommendations' => $recommendations['recommendations'] ?? [],
            'prediction_date' => now(),
            'target_date' => now()->addMonths(6),
            'is_confirmed' => false,
        ]);

        Cache::tags(['staff_predictions', 'staff:' . $staffId])->flush();

        $this->logAction('learning_recommendations_saved', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $prediction->id,
            'staff_id' => $staffId,
        ]);

        return $prediction;
    }

    /**
     * Сопоставляет навыки с задачами/проектами.
     */
    public function matchSkillsToTasks(int $staffId, array $requiredSkills): array
    {
        $staffSkills = StaffSkill::where('staff_id', $staffId)
            ->get()
            ->pluck('proficiency_level', 'name')
            ->toArray();

        $matches = [];

        foreach ($requiredSkills as $skill => $requiredLevel) {
            $currentLevel = $staffSkills[$skill] ?? 0;
            $gap = max(0, $requiredLevel - $currentLevel);

            $matches[$skill] = [
                'current_level' => $currentLevel,
                'required_level' => $requiredLevel,
                'gap' => $gap,
                'is_suitable' => $gap <= 20,
            ];
        }

        return $matches;
    }

    /**
     * Получает рекомендуемые курсы на основе skill gaps.
     */
    public function getRecommendedCourses(int $staffId): array
    {
        $cacheKey = "staff_recommended_courses:{$staffId}";

        return Cache::tags(['staff_learning', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($staffId) {
                $prediction = StaffPrediction::where('staff_id', $staffId)
                    ->where('prediction_type', 'skill_gap')
                    ->where('prediction_date', '>=', now()->subMonth())
                    ->latest()
                    ->first();

                if (!$prediction) {
                    return [];
                }

                return $prediction->prediction_data['recommended_courses'] ?? [];
            },
        );
    }

    /**
     * Анализирует пробелы в навыках команды.
     */
    public function analyzeTeamSkillGaps(int $tenantId, array $teamIds): array
    {
        $cacheKey = "team_skill_gaps:{$tenantId}:" . md5(implode(',', $teamIds));

        return Cache::tags(['staff_learning'])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($teamIds) {
                $allSkills = StaffSkill::whereIn('staff_id', $teamIds)
                    ->get()
                    ->groupBy('name');

                $skillAnalysis = [];

                foreach ($allSkills as $skillName => $skills) {
                    $avgProficiency = $skills->avg('proficiency_level');
                    $maxProficiency = $skills->max('proficiency_level');
                    $count = $skills->count();

                    $skillAnalysis[$skillName] = [
                        'average_proficiency' => round($avgProficiency, 2),
                        'max_proficiency' => $maxProficiency,
                        'staff_count' => $count,
                        'gap' => max(0, 80 - $avgProficiency),
                    ];
                }

                return $skillAnalysis;
            },
        );
    }

    /**
     * Генерирует план развития на основе AI-анализа.
     */
    public function generateDevelopmentPlan(int $staffId): array
    {
        $staff = Staff::findOrFail($staffId);

        // Получаем текущие навыки
        $currentSkills = StaffSkill::where('staff_id', $staffId)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name,
                'proficiency' => $s->proficiency_level,
                'category' => $s->category,
            ])
            ->toArray();

        // Получаем последние рекомендации
        $recommendations = $this->getRecommendedCourses($staffId);

        return [
            'staff_id' => $staffId,
            'current_skills' => $currentSkills,
            'recommended_courses' => $recommendations,
            'focus_areas' => $this->identifyFocusAreas($currentSkills),
            'timeline' => '6 months',
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Идентифицирует области для развития.
     */
    private function identifyFocusAreas(array $currentSkills): array
    {
        $lowSkills = array_filter($currentSkills, fn ($s) => $s['proficiency'] < 60);
        $categories = array_column($lowSkills, 'category');

        return array_unique($categories);
    }

    /**
     * Обновляет прогресс навыка после завершения курса.
     */
    public function updateSkillProgress(
        int $staffId,
        string $skillName,
        int $progressDelta,
    ): void {
        $skill = StaffSkill::where('staff_id', $staffId)
            ->where('name', $skillName)
            ->first();

        if ($skill) {
            $skill->update([
                'proficiency_level' => min(100, $skill->proficiency_level + $progressDelta),
            ]);
        } else {
            StaffSkill::create([
                'tenant_id' => Staff::findOrFail($staffId)->tenant_id,
                'staff_id' => $staffId,
                'name' => $skillName,
                'category' => 'technical',
                'proficiency_level' => $progressDelta,
                'years_of_experience' => 0,
                'is_certified' => false,
            ]);
        }

        Cache::tags(['staff_learning', 'staff:' . $staffId])->flush();

        $this->logAction('skill_progress_updated', [
            'entity_type' => 'staff_skill',
            'staff_id' => $staffId,
            'skill_name' => $skillName,
            'progress_delta' => $progressDelta,
        ]);
    }
}
