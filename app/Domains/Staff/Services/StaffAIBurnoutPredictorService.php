<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffPrediction;
use App\Domains\Staff\Domain\Entities\StaffWellnessMetric;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffAIBurnoutPredictorService — AI-предсказание выгорания и оттока сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Анализирует wellness-метрики, рабочую нагрузку, паттерны поведения
 * для выявления риска выгорания. LLM вызовы асинхронны через Jobs.
 */
final class StaffAIBurnoutPredictorService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Запускает анализ риска выгорания (асинхронно).
     */
    public function predictBurnoutRisk(int $staffId): void
    {
        // Fraud check
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_burnout_prediction',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            $this->logger->warning('Fraud detected in burnout prediction', [
                'staff_id' => $staffId,
                'fraud_reason' => $fraudResult->reason,
            ]);
            throw new \RuntimeException('Request blocked by fraud control');
        }

        // Диспетчеризация в Job
        \App\Jobs\Staff\PredictStaffBurnoutJob::dispatch(
            $staffId,
            auth()->id(),
        );

        $this->logAction('burnout_prediction_requested', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
        ]);
    }

    /**
     * Сохраняет результат предсказания выгорания (вызывается из Job).
     */
    public function saveBurnoutPrediction(
        int $staffId,
        array $predictionData,
    ): StaffPrediction {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_burnout_save',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        $riskLevel = $this->calculateRiskLevel($predictionData['risk_score'] ?? 0);

        $prediction = StaffPrediction::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'prediction_type' => 'burnout_risk',
            'confidence_score' => $predictionData['confidence_score'] ?? 0.75,
            'risk_level' => $riskLevel,
            'prediction_data' => $predictionData,
            'factors' => $predictionData['factors'] ?? [],
            'recommendations' => $predictionData['recommendations'] ?? [],
            'prediction_date' => now(),
            'target_date' => now()->addMonths(3),
            'is_confirmed' => false,
        ]);

        // Инвалидация кэша
        Cache::tags(['staff_predictions', 'staff:' . $staffId])->flush();

        $this->logAction('burnout_prediction_saved', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $prediction->id,
            'staff_id' => $staffId,
            'risk_level' => $riskLevel,
        ]);

        // Если высокий риск — уведомление
        if (in_array($riskLevel, ['high', 'critical'])) {
            $this->notifyHighBurnoutRisk($staffId, $prediction);
        }

        return $prediction;
    }

    /**
     * Запускает анализ риска оттока (асинхронно).
     */
    public function predictTurnoverRisk(int $staffId): void
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_turnover_prediction',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        \App\Jobs\Staff\PredictStaffTurnoverJob::dispatch($staffId, auth()->id());

        $this->logAction('turnover_prediction_requested', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
        ]);
    }

    /**
     * Сохраняет результат предсказания оттока.
     */
    public function saveTurnoverPrediction(
        int $staffId,
        array $predictionData,
    ): StaffPrediction {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_turnover_save',
            'staff_id' => $staffId,
            'tenant_id' => auth()->user()?->tenant_id ?? null,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $staff = Staff::findOrFail($staffId);

        $riskLevel = $this->calculateRiskLevel($predictionData['risk_score'] ?? 0);

        $prediction = StaffPrediction::create([
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staffId,
            'prediction_type' => 'turnover_risk',
            'confidence_score' => $predictionData['confidence_score'] ?? 0.75,
            'risk_level' => $riskLevel,
            'prediction_data' => $predictionData,
            'factors' => $predictionData['factors'] ?? [],
            'recommendations' => $predictionData['recommendations'] ?? [],
            'prediction_date' => now(),
            'target_date' => now()->addMonths(6),
            'is_confirmed' => false,
        ]);

        Cache::tags(['staff_predictions', 'staff:' . $staffId])->flush();

        $this->logAction('turnover_prediction_saved', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $prediction->id,
            'staff_id' => $staffId,
            'risk_level' => $riskLevel,
        ]);

        return $prediction;
    }

    /**
     * Получает последние предсказания для сотрудника.
     */
    public function getLatestPredictions(int $staffId): array
    {
        $cacheKey = "staff_predictions:{$staffId}";

        return Cache::tags(['staff_predictions', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staffId) {
                return StaffPrediction::where('staff_id', $staffId)
                    ->orderByDesc('prediction_date')
                    ->limit(10)
                    ->get()
                    ->groupBy('prediction_type')
                    ->map(fn ($group) => $group->first())
                    ->toArray();
            },
        );
    }

    /**
     * Получает сотрудников с высоким риском выгорания.
     */
    public function getHighBurnoutRiskStaff(int $tenantId): array
    {
        $cacheKey = "high_burnout_risk:{$tenantId}";

        return Cache::tags(['staff_predictions'])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId) {
                return StaffPrediction::where('tenant_id', $tenantId)
                    ->where('prediction_type', 'burnout_risk')
                    ->whereIn('risk_level', ['high', 'critical'])
                    ->where('prediction_date', '>=', now()->subMonth())
                    ->with('staff')
                    ->get()
                    ->map(fn ($p) => [
                        'staff_id' => $p->staff_id,
                        'staff_name' => $p->staff->full_name ?? 'Unknown',
                        'risk_level' => $p->risk_level,
                        'confidence_score' => $p->confidence_score,
                        'prediction_date' => $p->prediction_date->toDateString(),
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * Рассчитывает уровень риска на основе score.
     */
    private function calculateRiskLevel(float $score): string
    {
        return match(true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 40 => 'medium',
            $score >= 20 => 'low',
            default => 'minimal',
        };
    }

    /**
     * Уведомляет о высоком риске выгорания.
     */
    private function notifyHighBurnoutRisk(int $staffId, StaffPrediction $prediction): void
    {
        // Отправка уведомления менеджеру и HR
        \App\Jobs\Staff\NotifyBurnoutRiskJob::dispatch($staffId, $prediction->id);

        $this->logAction('burnout_risk_notification_sent', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $prediction->id,
            'staff_id' => $staffId,
        ]);
    }

    /**
     * Подтверждает предсказание (если событие произошло).
     */
    public function confirmPrediction(int $predictionId): void
    {
        $prediction = StaffPrediction::findOrFail($predictionId);
        $prediction->update(['is_confirmed' => true]);

        $this->logAction('prediction_confirmed', [
            'entity_type' => 'staff_prediction',
            'entity_id' => $predictionId,
        ]);
    }
}
