<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\B2BLeadCreated;
use Modules\CatCRM\Application\Services\CRMStaffIntegrationService;

final class AutoAssignLeadToStaff
{
    public function __construct(
        private readonly CRMStaffIntegrationService $staffIntegration,
    ) {}

    public function handle(B2BLeadCreated $event): void
    {
        $staff = $this->staffIntegration->autoAssignLead($event->lead);
        
        if ($staff) {
            \Log::info('Lead auto-assigned to staff', [
                'lead_id' => $event->lead->id,
                'staff_id' => $staff->id,
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
