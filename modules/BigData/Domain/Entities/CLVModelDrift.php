<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\DriftStatus;

/**
 * CLV Model Drift Entity
 *
 * Tracks ML model quality degradation for the CLV (Customer Lifetime Value) model.
 * Domain invariants:
 * - Accuracy values must be in [0, 1] or -1 for unknown
 * - Accuracy drop must be >= 0 (or -1 for unknown)
 * - Drift status is derived from accuracy drop / PSI, not set arbitrarily
 * - PSI must be >= 0 (or -1 for unknown)
 */
final class CLVModelDrift
{
    private function __construct(
        public readonly string $modelVersion,
        public readonly float $psiValue,
        public readonly float $ksStatistic,
        public readonly float $ksPValue,
        public readonly float $accuracyCurrent,
        public readonly float $accuracyBaseline,
        public readonly float $accuracyDrop,
        public readonly float $rmseCurrent,
        public readonly float $rmseBaseline,
        public readonly DriftStatus $driftStatus,
        public readonly CarbonImmutable $calculatedAt,
    ) {
        $this->validate();
    }

    /**
     * Factory: create from ClickHouse model metrics comparison
     */
    public static function fromMetrics(
        string $modelVersion,
        float $accuracyCurrent,
        float $accuracyBaseline,
        float $rmseCurrent = -1.0,
        float $rmseBaseline = -1.0,
        float $psiValue = -1.0,
        float $ksStatistic = -1.0,
        float $ksPValue = -1.0,
    ): self {
        $accuracyDrop = $accuracyCurrent >= 0 && $accuracyBaseline >= 0
            ? max(0, round($accuracyBaseline - $accuracyCurrent, 4))
            : -1.0;

        // Determine drift from accuracy drop (primary) or PSI (secondary)
        $driftStatus = match (true) {
            $accuracyDrop < 0 => DriftStatus::Unknown,
            $psiValue >= 0 && DriftStatus::fromPSI($psiValue)->isCritical() => DriftStatus::Critical,
            default => DriftStatus::fromAccuracyDrop($accuracyDrop),
        };

        return new self(
            modelVersion: $modelVersion,
            psiValue: $psiValue,
            ksStatistic: $ksStatistic,
            ksPValue: $ksPValue,
            accuracyCurrent: $accuracyCurrent,
            accuracyBaseline: $accuracyBaseline,
            accuracyDrop: $accuracyDrop,
            rmseCurrent: $rmseCurrent,
            rmseBaseline: $rmseBaseline,
            driftStatus: $driftStatus,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create when model metrics are unavailable
     */
    public static function unknown(): self
    {
        return new self(
            modelVersion: 'unknown',
            psiValue: -1.0,
            ksStatistic: -1.0,
            ksPValue: -1.0,
            accuracyCurrent: -1.0,
            accuracyBaseline: -1.0,
            accuracyDrop: -1.0,
            rmseCurrent: -1.0,
            rmseBaseline: -1.0,
            driftStatus: DriftStatus::Unknown,
            calculatedAt: CarbonImmutable::now(),
        );
    }

    /**
     * Factory: create from stored snapshot
     */
    public static function fromArray(array $data): self
    {
        return new self(
            modelVersion: $data['model_version'],
            psiValue: $data['psi_value'],
            ksStatistic: $data['ks_statistic'],
            ksPValue: $data['ks_p_value'],
            accuracyCurrent: $data['accuracy_current'],
            accuracyBaseline: $data['accuracy_baseline'],
            accuracyDrop: $data['accuracy_drop'],
            rmseCurrent: $data['rmse_current'],
            rmseBaseline: $data['rmse_baseline'],
            driftStatus: DriftStatus::from($data['drift_status']),
            calculatedAt: CarbonImmutable::parse($data['calculated_at']),
        );
    }

    /**
     * Business rule: does this drift require immediate retraining?
     */
    public function requiresImmediateRetraining(): bool
    {
        return $this->driftStatus->requiresRetraining();
    }

    /**
     * Business rule: should retraining be scheduled?
     */
    public function requiresScheduledRetraining(): bool
    {
        return $this->driftStatus->requiresScheduledRetraining();
    }

    /**
     * Business rule: is the model still usable for production predictions?
     * Model is usable if drift is none/low and accuracy >= 0.90
     */
    public function isUsableForProduction(): bool
    {
        return in_array($this->driftStatus, [DriftStatus::None, DriftStatus::Low], true)
            && $this->accuracyCurrent >= 0.90;
    }

    /**
     * Business rule: accuracy drop as percentage
     */
    public function accuracyDropPercent(): float
    {
        if ($this->accuracyDrop < 0) {
            return -1.0;
        }

        return round($this->accuracyDrop * 100, 2);
    }

    /**
     * Business rule: RMSE change ratio (current/baseline)
     * >1 means RMSE increased (worse), <1 means improved
     */
    public function rmseChangeRatio(): ?float
    {
        if ($this->rmseCurrent < 0 || $this->rmseBaseline <= 0) {
            return null;
        }

        return round($this->rmseCurrent / $this->rmseBaseline, 4);
    }

    /**
     * Business rule: PSI interpretation
     * <0.1 = negligible, 0.1-0.2 = slight, >0.2 = significant
     */
    public function psiInterpretation(): string
    {
        if ($this->psiValue < 0) {
            return 'unknown';
        }

        return match (true) {
            $this->psiValue < 0.1 => 'negligible',
            $this->psiValue < 0.2 => 'slight',
            $this->psiValue < 0.5 => 'significant',
            default => 'severe',
        };
    }

    /**
     * Business rule: compare with a previous drift measurement
     */
    public function isDriftWorsening(self $previous): bool
    {
        $severityOrder = [
            DriftStatus::None->value => 0,
            DriftStatus::Low->value => 1,
            DriftStatus::Medium->value => 2,
            DriftStatus::High->value => 3,
            DriftStatus::Critical->value => 4,
            DriftStatus::Unknown->value => -1,
        ];

        $currentLevel = $severityOrder[$this->driftStatus->value] ?? -1;
        $previousLevel = $severityOrder[$previous->driftStatus->value] ?? -1;

        return $currentLevel > $previousLevel;
    }

    public function hasDrift(): bool
    {
        return $this->driftStatus->hasDrift();
    }

    public function isCritical(): bool
    {
        return $this->driftStatus->isCritical();
    }

    public function toArray(): array
    {
        return [
            'model_version' => $this->modelVersion,
            'psi_value' => $this->psiValue,
            'psi_interpretation' => $this->psiInterpretation(),
            'ks_statistic' => $this->ksStatistic,
            'ks_p_value' => $this->ksPValue,
            'accuracy_current' => $this->accuracyCurrent,
            'accuracy_baseline' => $this->accuracyBaseline,
            'accuracy_drop' => $this->accuracyDrop,
            'accuracy_drop_percent' => $this->accuracyDropPercent(),
            'rmse_current' => $this->rmseCurrent,
            'rmse_baseline' => $this->rmseBaseline,
            'rmse_change_ratio' => $this->rmseChangeRatio(),
            'drift_status' => $this->driftStatus->value,
            'requires_retraining' => $this->requiresImmediateRetraining(),
            'is_usable_for_production' => $this->isUsableForProduction(),
            'calculated_at' => $this->calculatedAt->toIso8601String(),
        ];
    }

    private function validate(): void
    {
        if (trim($this->modelVersion) === '') {
            throw new \InvalidArgumentException('CLVModelDrift modelVersion cannot be empty');
        }

        if ($this->accuracyCurrent < -1 || $this->accuracyCurrent > 1) {
            throw new \InvalidArgumentException('CLVModelDrift accuracyCurrent must be in [-1, 1]');
        }

        if ($this->accuracyBaseline < -1 || $this->accuracyBaseline > 1) {
            throw new \InvalidArgumentException('CLVModelDrift accuracyBaseline must be in [-1, 1]');
        }

        if ($this->accuracyDrop < -1) {
            throw new \InvalidArgumentException('CLVModelDrift accuracyDrop must be >= -1');
        }

        if ($this->psiValue < -1) {
            throw new \InvalidArgumentException('CLVModelDrift psiValue must be >= -1');
        }
    }
}
