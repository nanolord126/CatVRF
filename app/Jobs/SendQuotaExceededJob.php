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
use App\Mail\QuotaExceededMail;

/**
 * Send Quota Exceeded Job
 *
 * Production 2026 CANON - Quota Alert System
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class SendQuotaExceededJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId,
        private int $tenantId,
        private string $resourceType,
        private array $quotaData,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        
        if (!$user) {
            Log::warning('User not found for quota exceeded notification', [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
            ]);
            return;
        }

        try {
            Mail::to($user->email)->send(new QuotaExceededMail(
                $this->tenantId,
                $this->resourceType,
                $this->quotaData
            ));

            Log::info('Quota exceeded email sent', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'resource_type' => $this->resourceType,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send quota exceeded email', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
