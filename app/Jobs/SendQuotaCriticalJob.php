<?php declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuotaCriticalMail;

/**
 * Send Quota Critical Job
 *
 * Production 2026 CANON - Quota Alert System
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class SendQuotaCriticalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tenantId,
        private string $resourceType,
        private array $quotaData,
    ) {}

    public function handle(): void
    {
        $recipients = User::where('tenant_id', $this->tenantId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Manager', 'Admin']);
            })
            ->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new QuotaCriticalMail(
                    $this->tenantId,
                    $this->resourceType,
                    $this->quotaData
                ));

                Log::info('Quota critical email sent', [
                    'tenant_id' => $this->tenantId,
                    'user_id' => $recipient->id,
                    'resource_type' => $this->resourceType,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send quota critical email', [
                    'tenant_id' => $this->tenantId,
                    'user_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
