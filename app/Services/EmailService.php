<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final readonly class EmailService
{
    public function __construct(
        private RateLimiterService $rateLimiterService,
    ) {}

    public function sendDailyReport(int $tenantId, array $data, string $correlationId = ''): void
    {
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);

            Mail::send('emails.daily-report', $data, function ($message) use ($tenant) {
                $message->to($tenant->email)
                    ->subject('Ежедневный отчёт — ' . date('d.m.Y'));
            });

            Log::channel('audit')->info('Daily report sent', [
                'tenant_id' => $tenantId,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Daily report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
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

            Log::channel('audit')->info('Weekly report sent', [
                'tenant_id' => $tenantId,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Weekly report send failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendTransactionalEmail(string $email, string $template, array $data): void
    {
        try {
            Mail::send("emails.$template", $data, function ($message) use ($email) {
                $message->to($email);
            });

            Log::channel('audit')->info('Transactional email sent', [
                'email' => $email,
                'template' => $template,
            ]);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Transactional email send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
