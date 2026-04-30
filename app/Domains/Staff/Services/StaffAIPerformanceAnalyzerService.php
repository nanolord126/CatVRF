<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffAiAnalytics;
use App\Domains\Staff\Domain\Entities\StaffPrediction;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffAIPerformanceAnalyzerService — AI-анализ производительности сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Анализирует паттерны производительности, выявляет сильные/слабые стороны,
 * генерирует рекомендации. LLM вызовы асинхронны через Jobs.
 */
final class StaffAIPerformanceAnalyzerService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Запускает анализ производительности сотрудника (асинхронно).
     */
    public function analyzePerformance(int $staffId, Carbon $periodStart, Carbon $periodEnd): void
    {
        // Fraud check — первое действие
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_performance_analysis',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            $this->logger->warning('Fraud detected in performance analysis', [
                'staff_id' => $staffId,
                'fraud_reason' => $fraudResult->reason,
            ]);
            throw new \RuntimeException('Request blocked by fraud control');
        }

        // Диспетчеризация в Job для асинхронной обработки
        \App\Jobs\Staff\AnalyzeStaffPerformanceJob::dispatch(
            $staffId,
            $periodStart,
            $periodEnd,
            auth()->id(),
        );

        $this->logAction('performance_analysis_requested', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ]);
    }

    /**
     * Сохраняет результаты AI-анализа (вызывается из Job).
     */
    public function saveAnalysisResult(
        int $staffId,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $analysisData,
    ): StaffAiAnalytics {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_performance_save',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        $analytics = StaffAiAnalytics::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'ai_performance_score' => $analysisData['performance_score'] ?? 0,
            'ai_efficiency_score' => $analysisData['efficiency_score'] ?? 0,
            'ai_quality_score' => $analysisData['quality_score'] ?? 0,
            'ai_collaboration_score' => $analysisData['collaboration_score'] ?? 0,
            'patterns' => $analysisData['patterns'] ?? [],
            'strengths' => $analysisData['strengths'] ?? [],
            'weaknesses' => $analysisData['weaknesses'] ?? [],
            'recommendations' => $analysisData['recommendations'] ?? [],
            'team_percentile' => $analysisData['team_percentile'] ?? 50,
        ]);

        // Инвалидация кэша
        Cache::tags(['staff_analytics', 'staff:' . $staffId])->flush();

        $this->logAction('performance_analytics_saved', [
            'entity_type' => 'staff_ai_analytics',
            'entity_id' => $analytics->id,
            'staff_id' => $staffId,
            'period' => $periodStart->toDateString() . ' - ' . $periodEnd->toDateString(),
        ]);

        return $analytics;
    }

    /**
     * Получает анализ производительности (с кэшированием).
     */
    public function getAnalytics(int $staffId, Carbon $periodStart, Carbon $periodEnd): ?StaffAiAnalytics
    {
        $cacheKey = "staff_analytics:{$staffId}:{$periodStart->toDateString()}:{$periodEnd->toDateString()}";

        return Cache::tags(['staff_analytics', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            fn () => StaffAiAnalytics::where('staff_id', $staffId)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->first(),
        );
    }

    /**
     * Получает тренд производительности за период.
     */
    public function getPerformanceTrend(int $staffId, int $months = 6): array
    {
        $cacheKey = "staff_performance_trend:{$staffId}:{$months}m";

        return Cache::tags(['staff_analytics', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($staffId, $months) {
                return StaffAiAnalytics::where('staff_id', $staffId)
                    ->where('period_start', '>=', now()->subMonths($months))
                    ->orderBy('period_start')
                    ->get()
                    ->map(fn ($a) => [
                        'period' => $a->period_start->toDateString(),
                        'overall_score' => $a->overall_score,
                        'performance' => $a->ai_performance_score,
                        'efficiency' => $a->ai_efficiency_score,
                        'quality' => $a->ai_quality_score,
                        'collaboration' => $a->ai_collaboration_score,
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Выявляет сотрудников с падением производительности.
     */
    public function identifyDecliningPerformers(int $threshold = 15): array
    {
        $cacheKey = 'staff_declining_performers:' . $threshold;

        return Cache::tags(['staff_analytics'])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($threshold) {
                $twoMonthsAgo = now()->subMonths(2);
                $lastMonth = now()->subMonth();

                $previous = StaffAiAnalytics::where('period_start', '>=', $twoMonthsAgo->startOfMonth())
                    ->where('period_end', '<=', $twoMonthsAgo->endOfMonth())
                    ->get()
                    ->keyBy('staff_id');

                $current = StaffAiAnalytics::where('period_start', '>=', $lastMonth->startOfMonth())
                    ->where('period_end', '<=', $lastMonth->endOfMonth())
                    ->get()
                    ->keyBy('staff_id');

                $declining = [];

                foreach ($current as $staffId => $currentAnalytics) {
                    if (!isset($previous[$staffId])) {
                        continue;
                    }

                    $previousScore = $previous[$staffId]->overall_score;
                    $currentScore = $currentAnalytics->overall_score;

                    if ($previousScore > 0 && $currentScore < ($previousScore - $threshold)) {
                        $declining[] = [
                            'staff_id' => $staffId,
                            'previous_score' => $previousScore,
                            'current_score' => $currentScore,
                            'decline' => $previousScore - $currentScore,
                        ];
                    }
                }

                return $declining;
            },
        );
    }

    /**
     * Генерирует предсказание тренда производительности.
     */
    public function predictPerformanceTrend(int $staffId): StaffPrediction
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_performance_predict',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        // Создаём предсказание (реальный AI вызов будет в Job)
        $prediction = StaffPrediction::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'prediction_type' => 'performance_trend',
            'confidence_score' => 0.75,
            'risk_level' => 'medium',
            'prediction_data' => [
                'trend' => 'stable',
                'expected_score' => 75,
                'change_percent' => 0,
            ],
            'factors' => [
                'recent_performance',
                'attendance',
                'team_feedback',
            ],
            'recommendations' => [
                'Continue current performance level',
                'Focus on collaboration metrics',
            ],
            'prediction_date' => now(),
            'target_date' => now()->addMonth(),
        ]);

        $this->logAction('performance_prediction_created', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $prediction->id,
            'staff_id' => $staffId,
        ]);

        return $prediction;
    }
}
