<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Events;

use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * KPICalculated — Событие расчета KPI
 */
final class KPICalculated
{
    use Dispatchable;

    public function __construct(
        public readonly ManagerKPI $kpi,
        public readonly float $previousScore
    ) {}
}
