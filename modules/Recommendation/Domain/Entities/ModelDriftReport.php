<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Entities;

use Modules\Recommendation\Domain\Enums\DriftStatus;

final class ModelDriftReport
{
    private function __construct(
        private readonly string $modelType,
        private readonly string $modelVersion,
        private readonly float $psiValue,
        private readonly float $accuracyDrop,
        private readonly float $ndcgDrop,
        private readonly DriftStatus $status,
        private readonly \DateTimeImmutable $checkedAt,
    ) {}

    public static function fromMetrics(
        string $modelType,
        string $modelVersion,
        float $psiValue,
        float $accuracyDrop,
        float $ndcgDrop,
    ): self {
        return new self(
            modelType: $modelType,
            modelVersion: $modelVersion,
            psiValue: $psiValue,
            accuracyDrop: $accuracyDrop,
            ndcgDrop: $ndcgDrop,
            status: DriftStatus::fromPsiAndAccuracy($psiValue, $accuracyDrop),
            checkedAt: new \DateTimeImmutable(),
        );
    }

    public function requiresRetraining(): bool
    {
        return $this->status === DriftStatus::CRITICAL;
    }

    public function getModelType(): string { return $this->modelType; }
    public function getModelVersion(): string { return $this->modelVersion; }
    public function getPsiValue(): float { return $this->psiValue; }
    public function getAccuracyDrop(): float { return $this->accuracyDrop; }
    public function getNdcgDrop(): float { return $this->ndcgDrop; }
    public function getStatus(): DriftStatus { return $this->status; }
    public function getCheckedAt(): \DateTimeImmutable { return $this->checkedAt; }

    public function toArray(): array
    {
        return [
            'model_type' => $this->modelType,
            'model_version' => $this->modelVersion,
            'psi_value' => $this->psiValue,
            'accuracy_drop' => $this->accuracyDrop,
            'ndcg_drop' => $this->ndcgDrop,
            'status' => $this->status->value,
            'checked_at' => $this->checkedAt->format('c'),
            'requires_retraining' => $this->requiresRetraining(),
        ];
    }
}
