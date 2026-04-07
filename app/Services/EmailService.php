<?php declare(strict_types=1);

namespace App\Services;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Log\LogManager;

final readonly class EmailService
{
    public function __construct(
        private readonly Request $request,
        private RateLimiterService $rateLimiterService,
        private readonly LogManager $logger,
    ) {}

    public function sendDailyReport(int $tenantId, array $data, string $correlationId = ''): void
    {
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);

            Mail::send('emails.daily-report', $data, function ($message) use ($tenant) {
                $message->to($tenant->email)
                    ->subject('Ежедневный отчёт — ' . date('d.m.Y'));
            });

            $this->logger->channel('audit')->info('Daily report sent', [
                'tenant_id' => $tenantId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Daily report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }
    }

    public function sendWeeklyReport(int $tenantId, array $data): void
    {
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);

            Mail::send('emails.weekly-report', $data, function ($message) use ($tenant) {
                $message->to($tenant->email)
                    ->subject('Еженедельный отчёт — ' . date('W, Y'));
            });

            $this->logger->channel('audit')->info('Weekly report sent', [
                'tenant_id' => $tenantId,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Weekly report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }
    }

    public function sendTransactionalEmail(string $email, string $template, array $data): void
    {
        try {
            Mail::send("emails.$template", $data, function ($message) use ($email) {
                $message->to($email);
            });

            $this->logger->channel('audit')->info('Transactional email sent', [
                'email' => $email,
                'template' => $template,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('Transactional email send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
        }
    }
}
