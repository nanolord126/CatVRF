<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Modules\CatCRM\Domain\Entities\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to automatically segment customers based on their behavior
 */
final class AutoSegmentCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging, WithTelemetry;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $businessGroupId = null
    ) {}

    public function handle(SupermarketCRMService $crmService): void
    {
        $this->withSpan(
            'supermarket_crm.auto_segment_customers_job',
            function () use ($crmService) {
                $query = Customer::where('tenant_id', $this->tenantId);

                if ($this->businessGroupId !== null) {
                    $query->where('business_group_id', $this->businessGroupId);
                }

                $customers = $query->get();

                foreach ($customers as $customer) {
                    $crmService->updateCustomerStatistics($customer);
                }

                $this->logAction('crm_customers_auto_segmented', $this->tenantId, [
                    'tenant_id' => $this->tenantId,
                    'business_group_id' => $this->businessGroupId,
                    'customers_count' => $customers->count(),
                    'vertical' => 'supermarket',
                ]);
            },
            $this->getStandardAttributes('supermarket', 'auto_segment_customers_job')
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->recordSpanException($exception);

        \Log::error('Failed to auto segment customers', [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'error' => $exception->getMessage(),
        ]);
    }
}
