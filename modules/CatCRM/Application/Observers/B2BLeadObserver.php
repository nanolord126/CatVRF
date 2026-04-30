<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Observers;

use Modules\CatCRM\Domain\Entities\B2BLead;
use Modules\CatCRM\Domain\Events\B2BLeadCreated;
use Modules\CatCRM\Domain\Events\B2BLeadConverted;
use Modules\CatCRM\Application\Services\CRMCacheService;
use Illuminate\Support\Facades\Event;

final class B2BLeadObserver
{
    public function __construct(
        private readonly CRMCacheService $cacheService,
    ) {}

    public function created(B2BLead $lead): void
    {
        Event::dispatch(new B2BLeadCreated($lead, $lead->correlation_id ?? \Str::uuid()));
    }

    public function updated(B2BLead $lead): void
    {
        $this->cacheService->invalidateLeadCache($lead->id);
        $this->cacheService->invalidateTenantCache($lead->tenant_id);
    }

    public function deleted(B2BLead $lead): void
    {
        $this->cacheService->invalidateLeadCache($lead->id);
        $this->cacheService->invalidateTenantCache($lead->tenant_id);
    }

    public function converting(B2BLead $lead): void
    {
        Event::dispatch(new B2BLeadConverted(
            $lead,
            $lead->deals()->latest()->first(),
            $lead->correlation_id ?? \Str::uuid()
        ));
    }
}
