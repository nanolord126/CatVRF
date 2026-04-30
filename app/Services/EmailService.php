<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use Illuminate\Mail\Mailer;
use Illuminate\Log\LogManager;
use App\Models\Tenant;

final readonly class EmailService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly RateLimiterService $rateLimiterService,
        private readonly AuditService $auditService,
        private readonly Mailer $mailer,
    ) {}

    public function sendDailyReport(int $tenantId, array $data, string $correlationId = ''): void
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $this->mailer->send('emails.daily-report', $data, function ($message) use ($tenant) {
                $message->to($tenant->email)
                    ->subject('Ежедневный отчёт — '.date('d.m.Y'));
            });

            $this->logger->channel('audit')->info('Daily report sent', [
                'tenant_id' => $tenantId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId),
            ]);
            $this->logAction('daily_report_sent', 'Email', null, [
                'tenant_id' => $tenantId,
            ], null, $tenantId, $this->request->header('X-Correlation-ID', $correlationId));
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Daily report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId),
            ]);
            $this->logAction('daily_report_failed', 'Email', null, [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], null, $tenantId, $this->request->header('X-Correlation-ID', $correlationId));
        }
    }

    public function sendWeeklyReport(int $tenantId, array $data): void
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);

            $this->mailer->send('emails.weekly-report', $data, function ($message) use ($tenant) {
                $message->to($tenant->email)
                    ->subject('Еженедельный отчёт — '.date('W, Y'));
            });

            $this->logger->channel('audit')->info('Weekly report sent', [
                'tenant_id' => $tenantId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId ?? ''),
            ]);
            $this->logAction('weekly_report_sent', 'Email', null, [
                'tenant_id' => $tenantId,
            ], null, $tenantId, $this->request->header('X-Correlation-ID', $correlationId ?? ''));
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Weekly report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId ?? ''),
            ]);
            $this->logAction('weekly_report_failed', 'Email', null, [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], null, $tenantId, $this->request->header('X-Correlation-ID', $correlationId ?? ''));
        }
    }

    public function sendTransactionalEmail(string $email, string $template, array $data): void
    {
        try {
            $this->mailer->send("emails.$template", $data, function ($message) use ($email) {
                $message->to($email);
            });

            $this->logger->channel('audit')->info('Transactional email sent', [
                'email' => $email,
                'template' => $template,
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId ?? ''),
            ]);
            $this->logAction('transactional_email_sent', 'Email', null, [
                'email' => $email,
                'template' => $template,
            ], null, null, $this->request->header('X-Correlation-ID', $correlationId ?? ''));
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Transactional email send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $correlationId ?? ''),
            ]);
            $this->logAction('transactional_email_failed', 'Email', null, [
                'email' => $email,
                'error' => $e->getMessage(),
            ], null, null, $this->request->header('X-Correlation-ID', $correlationId ?? ''));
        }
    }
}
