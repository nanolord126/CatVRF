<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Listeners;

use Modules\CatCRM\Domain\Events\B2BDealWon;
use Modules\CatCRM\Application\Services\CustomerService;
use Modules\CatCRM\Domain\Entities\Customer;

final class UpdateCustomerLTVOnDealWon
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function handle(B2BDealWon $event): void
    {
        // Find or create customer from deal
        $customer = Customer::where('tenant_id', $event->deal->tenant_id)
            ->where('company_name', $event->deal->company_name)
            ->first();

        if ($customer) {
            $this->customerService->updateCustomerStatistics($customer);
        }
    }
}
