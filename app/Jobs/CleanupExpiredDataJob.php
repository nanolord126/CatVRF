<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Security\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * Cleanup Expired Data Job
 * 
 * Removes expired data according to 152-ФZ retention requirements.
 * Automatically deletes old audit logs, temporary data, and consent-withdrawn records.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Compliance (152-ФЗ).
 */
final readonly class CleanupExpiredDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RETENTION_DAYS = 30; // 152-ФЗ requirement

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly ?int $tenantId = null,
    ) {
        $this->onQueue('maintenance');
    }

    /**
     * Execute the job.
     */
    public function handle(AuditService $auditService): void
    {
        $cutoffDate = CarbonImmutable::now()->subDays(self::RETENTION_DAYS);
        $deletedCounts = [];

        // Cleanup audit logs
        $deletedCounts['audit_logs'] = $this->cleanupAuditLogs($cutoffDate);

        // Cleanup temporary tokens
        $deletedCounts['temporary_tokens'] = $this->cleanupTemporaryTokens($cutoffDate);

        // Cleanup expired cooldown periods
        $deletedCounts['expired_cooldowns'] = $this->cleanupExpiredCooldowns($cutoffDate);

        // Cleanup consent-withdrawn user data
        $deletedCounts['consent_withdrawn'] = $this->cleanupConsentWithdrawn($cutoffDate);

        // Log cleanup results
        $auditService->logEvent('data_cleanup_completed', [
            'tenant_id' => $this->tenantId,
            'cutoff_date' => $cutoffDate->toIso8601String(),
            'deleted_counts' => $deletedCounts,
            'total_deleted' => array_sum($deletedCounts),
        ], 'compliance');

        Log::info('Data cleanup completed', [
            'tenant_id' => $this->tenantId,
            'cutoff_date' => $cutoffDate->toIso8601String(),
            'deleted_counts' => $deletedCounts,
        ]);
    }

    /**
     * Cleanup audit logs older than retention period
     */
    private function cleanupAuditLogs(CarbonImmutable $cutoffDate): int
    {
        $query = DB::table('audit_logs')
            ->where('created_at', '<', $cutoffDate);

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query->delete();
    }

    /**
     * Cleanup temporary tokens (password reset, email verification, etc.)
     */
    private function cleanupTemporaryTokens(CarbonImmutable $cutoffDate): int
    {
        $count = 0;

        // Password reset tokens
        $count += DB::table('password_reset_tokens')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        // Email verification tokens
        $count += DB::table('email_verification_tokens')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        // API tokens
        $count += DB::table('personal_access_tokens')
            ->where('created_at', '<', $cutoffDate)
            ->where('expires_at', '<', now())
            ->delete();

        return $count;
    }

    /**
     * Cleanup expired cooldown periods
     */
    private function cleanupExpiredCooldowns(CarbonImmutable $cutoffDate): int
    {
        $query = DB::table('cooldown_periods')
            ->where('expires_at', '<', $cutoffDate)
            ->where('status', 'expired');

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query->delete();
    }

    /**
     * Cleanup data from users who withdrew consent
     * 
     * NOTE: This is a simplified implementation. In production, you should:
     * - Anonymize data instead of deleting (for audit trail)
     * - Keep minimal legal records
     * - Follow legal counsel guidance
     */
    private function cleanupConsentWithdrawn(CarbonImmutable $cutoffDate): int
    {
        $count = 0;

        // Find users who withdrew consent and whose data is past retention
        $usersToCleanup = DB::table('users')
            ->where('consent_data_processing', false)
            ->where('consent_given_at', '<', $cutoffDate)
            ->when($this->tenantId !== null, fn ($q) => $q->where('tenant_id', $this->tenantId))
            ->get();

        foreach ($usersToCleanup as $user) {
            // Anonymize sensitive data instead of hard delete
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'email' => 'deleted_' . $user->id . '@deleted.local',
                    'phone' => null,
                    'inn' => null,
                    'first_name' => 'Deleted',
                    'last_name' => 'User',
                    'middle_name' => null,
                    'meta' => json_encode(['deleted_at' => now()->toIso8601String()]),
                ]);

            $count++;
        }

        return $count;
    }
}
