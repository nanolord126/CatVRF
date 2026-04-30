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
 * Job to update customer statistics asynchronously
 */
final class UpdateCustomerStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging, WithTelemetry;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $customerId
    ) {}

    public function handle(SupermarketCRMService $crmService): void
    {
        $this->withSpan(
            'supermarket_crm.update_customer_statistics_job',
            function () use ($crmService) {
                $customer = Customer::find($this->customerId);

                if ($customer) {
                    $crmService->updateCustomerStatistics($customer);

                    $this->logAction('crm_customer_statistics_updated', $this->customerId, [
                        'tenant_id' => $customer->tenant_id,
                        'vertical' => 'supermarket',
                    ]);
                }
            },
            $this->getStandardAttributes('supermarket', 'update_customer_statistics_job')
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->recordSpanException($exception);

        \Log::error('Failed to update customer statistics', [
            'customer_id' => $this->customerId,
            'error' => $exception->getMessage(),
        ]);
    }
}
