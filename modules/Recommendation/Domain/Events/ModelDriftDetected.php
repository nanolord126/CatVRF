<?php

declare(strict_types=1);

namespace Modules\Recommendation\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Recommendation\Domain\Enums\DriftStatus;

final readonly class ModelDriftDetected
{
    use Dispatchable;

    public function __construct(
        public string $modelType,
        public string $modelVersion,
        public DriftStatus $driftStatus,
        public float $psiValue,
        public float $accuracyDrop,
        public bool $requiresRetraining,
    ) {}
}
