<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Observers;

use Modules\CatCRM\Domain\Entities\Customer;
use Modules\CatCRM\Application\Services\CRMCacheService;

final class CustomerObserver
{
    public function __construct(
        private readonly CRMCacheService $cacheService,
    ) {}

    public function updated(Customer $customer): void
    {
        $this->cacheService->invalidateCustomerCache($customer->id);
        $this->cacheService->invalidateTenantCache($customer->tenant_id);
    }

    public function deleted(Customer $customer): void
    {
        $this->cacheService->invalidateCustomerCache($customer->id);
        $this->cacheService->invalidateTenantCache($customer->tenant_id);
    }
}
