<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use App\Mail\QuotaWarningMail;

/**
 * Send Quota Warning Job
 *
 * Production 2026 CANON - Quota Alert System
 *
 * @author CatVRF Team
 *
 * @version 2026.04.17
 */
final readonly class SendQuotaWarningJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $tenantId,
        private readonly string $resourceType,
        private readonly array $quotaData,
        private readonly Mailer $mailer,
        private readonly LogManager $log,
    ,
        public readonly string $correlationId = '') {}

    public function handle(): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $recipients = User::where('tenant_id', $this->tenantId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Manager', 'Admin']);
            })
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $this->mailer->to($recipient->email)->send(new QuotaWarningMail(
                    $this->tenantId,
                    $this->resourceType,
                    $this->quotaData
                ));

                $this->log->$this->logger->info('Quota warning email sent', [
                    'tenant_id' => $this->tenantId,
                    'user_id' => $recipient->id,
                    'resource_type' => $this->resourceType,
                ]);
            } catch (\Throwable $e) {
                $this->log->error('Failed to send quota warning email', [
                    'tenant_id' => $this->tenantId,
                    'user_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
