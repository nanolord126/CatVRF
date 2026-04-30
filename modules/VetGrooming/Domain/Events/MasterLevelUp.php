<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\VetGrooming\Domain\Entities\ProfessionalDevelopmentPlan;

final class MasterLevelUp
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ProfessionalDevelopmentPlan $developmentPlan,
        public readonly string $previousLevel,
        public readonly string $newLevel,
        public readonly int $masterId,
        public readonly int $tenantId,
    ) {}
}
