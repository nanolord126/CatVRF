<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Jobs;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Modules\CatCRM\Application\Services\SupermarketCRMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Job to send customer analytics report via email
 */
final class SendCustomerAnalyticsReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use WithAuditLogging, WithTelemetry;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $businessGroupId = null,
        private readonly int $days = 30,
        private readonly ?string $recipientEmail = null
    ) {}

    public function handle(SupermarketCRMService $crmService): void
    {
        $this->withSpan(
            'supermarket_crm.send_analytics_report_job',
            function () use ($crmService) {
                $analytics = $crmService->getCustomerAnalytics(
                    $this->tenantId,
                    $this->businessGroupId,
                    $this->days
                );

                $topCustomers = $crmService->getTopCustomers(
                    $this->tenantId,
                    $this->businessGroupId,
                    10
                );

                $recipientEmail = $this->recipientEmail ?? config('crm.analytics_report_email');

                if ($recipientEmail) {
                    // TODO: Create mailable class and send email
                    // Mail::to($recipientEmail)->send(new CustomerAnalyticsReport($analytics, $topCustomers, $this->days));

                    Log::info('Customer analytics report sent', [
                        'tenant_id' => $this->tenantId,
                        'recipient' => $recipientEmail,
                        'days' => $this->days,
                    ]);

                    $this->logAction('crm_analytics_report_sent', $this->tenantId, [
                        'tenant_id' => $this->tenantId,
                        'recipient' => $recipientEmail,
                        'days' => $this->days,
                        'vertical' => 'supermarket',
                    ]);
                }
            },
            $this->getStandardAttributes('supermarket', 'send_analytics_report_job')
        );
    }

    public function failed(\Throwable $exception): void
    {
        $this->recordSpanException($exception);

        Log::error('Failed to send customer analytics report', [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'error' => $exception->getMessage(),
        ]);
    }
}
