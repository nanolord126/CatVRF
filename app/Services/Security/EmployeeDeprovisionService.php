<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Events\EmployeeRevoked;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WebauthnCredential;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\CarbonImmutable;

final class EmployeeDeprovisionService
{
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}
    /**
     * Revoke employee access from tenant
     */
    public function revokeAccess(
        User $employee,
        Tenant $tenant,
        User $revokedBy,
        string $reason,
        bool $requiresMultiOwnerConfirmation = false
    ): DeprovisionResult {
        // Multi-owner safeguard check
        if ($requiresMultiOwnerConfirmation && ! $this->hasMultiOwnerConfirmation($tenant, $revokedBy)) {
            return DeprovisionResult::failure('Multi-owner confirmation required');
        }

        try {
            $this->db->beginTransaction();

            // Revoke user access
            $employee->revokeAccess($revokedBy->id, $reason);

            // Remove from tenant
            $tenant->users()->updateExistingPivot($employee->id, [
                'is_active' => false,
            ]);

            // Revoke all Sanctum tokens for this tenant
            $this->revokeSanctumTokens($employee, $tenant);

            // Revoke all Passkey credentials for this tenant
            $this->revokePasskeyCredentials($employee, $tenant);

            // Kill all sessions
            $this->killAllSessions($employee, $tenant);

            // Block all devices for this tenant
            $this->blockAllDevices($employee, $tenant);

            // Set cool-down period
            $this->setCoolDownPeriod($employee, $tenant);

            // Log deprovisioning
            $this->logDeprovisioning($employee, $tenant, $revokedBy, $reason);

            $this->db->commit();

            // Dispatch event for notifications
            $this->eventDispatcher->dispatch(new EmployeeRevoked(
                employee: $employee,
                tenant: $tenant,
                revokedBy: $revokedBy,
                reason: $reason,
            ));

            return DeprovisionResult::success([
                'sanctum_tokens_revoked' => true,
                'passkeys_revoked' => true,
                'sessions_killed' => true,
                'devices_blocked' => true,
                'cooldown_period_set' => true,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log->error('Employee deprovisioning failed', [
                'employee_id' => $employee->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return DeprovisionResult::failure($e->getMessage());
        }
    }

    /**
     * Full deprovisioning (including global revocation for critical threats)
     */
    public function fullDeprovision(
        User $employee,
        User $revokedBy,
        string $reason,
        bool $globalBan = false
    ): DeprovisionResult {
        try {
            $this->db->beginTransaction();

            // Get all tenants for this employee
            $tenants = $employee->tenants;

            foreach ($tenants as $tenant) {
                $this->revokeAccess($employee, $tenant, $revokedBy, $reason);
            }

            // Global ban if requested (for critical insider threats)
            if ($globalBan) {
                $employee->update(['is_active' => false]);
                $this->revokeAllSanctumTokens($employee);
                $this->revokeAllPasskeyCredentials($employee);
                $this->killAllGlobalSessions($employee);
                $this->blockAllGlobalDevices($employee);
            }

            $this->db->commit();

            return DeprovisionResult::success([
                'global_ban' => $globalBan,
                'tenants_affected' => $tenants->count(),
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log->error('Full employee deprovisioning failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return DeprovisionResult::failure($e->getMessage());
        }
    }

    /**
     * Store multi-owner confirmation
     */
    public function storeMultiOwnerConfirmation(Tenant $tenant, User $confirmingOwner, User $requester): void
    {
        $confirmationKey = "multi_owner_confirm:{$tenant->id}:{$requester->id}";
        $this->redis->setex($confirmationKey, 3600, $confirmingOwner->id); // 1 hour TTL
    }

    /**
     * Check if employee is in cool-down period
     */
    public function isInCoolDownPeriod(User $employee, Tenant $tenant): bool
    {
        $cooldownKey = "employee_cooldown:{$tenant->id}:{$employee->id}";

        return $this->redis->exists($cooldownKey) === 1;
    }

    /**
     * Get cool-down expiry timestamp
     */
    public function getCoolDownExpiry(User $employee, Tenant $tenant): ?\DateTime
    {
        $cooldownKey = "employee_cooldown:{$tenant->id}:{$employee->id}";
        $timestamp = $this->redis->get($cooldownKey);

        return $timestamp ? new \DateTime($timestamp) : null;
    }

    /**
     * Restore employee access (for reversals)
     */
    public function restoreAccess(
        User $employee,
        Tenant $tenant,
        User $restoredBy,
        string $reason
    ): DeprovisionResult {
        try {
            $this->db->beginTransaction();

            // Restore user access
            $employee->restoreAccess();

            // Re-add to tenant
            $tenant->users()->updateExistingPivot($employee->id, [
                'is_active' => true,
            ]);

            // Remove cool-down period
            $cooldownKey = "employee_cooldown:{$tenant->id}:{$employee->id}";
            $this->redis->del($cooldownKey);

            // Log restoration
            $this->log->channel('audit')->$this->logger->info('Employee access restored', [
                'employee_id' => $employee->id,
                'tenant_id' => $tenant->id,
                'restored_by' => $restoredBy->id,
                'reason' => $reason,
                'timestamp' => CarbonImmutable::now()->toIso8601String(),
            ]);

            $this->db->commit();

            return DeprovisionResult::success([
                'access_restored' => true,
                'cooldown_removed' => true,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->log->error('Employee access restoration failed', [
                'employee_id' => $employee->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return DeprovisionResult::failure($e->getMessage());
        }
    }

    /**
     * Check if multi-owner confirmation is present
     */
    private function hasMultiOwnerConfirmation(Tenant $tenant, User $requester): bool
    {
        // Get all owners of the tenant
        $owners = $tenant->users()
            ->wherePivot('role', 'owner')
            ->wherePivot('is_active', true)
            ->get();

        // If only one owner, no confirmation needed
        if ($owners->count() <= 1) {
            return true;
        }

        // Check if we have confirmation from another owner
        $confirmationKey = "multi_owner_confirm:{$tenant->id}:{$requester->id}";
        $hasConfirmation = $this->redis->exists($confirmationKey);

        return $hasConfirmation === 1;
    }

    /**
     * Revoke Sanctum tokens for specific tenant
     */
    private function revokeSanctumTokens(User $employee, Tenant $tenant): void
    {
        PersonalAccessToken::where('tokenable_id', $employee->id)
            ->where('tokenable_type', User::class)
            ->where('tenant_id', $tenant->id)
            ->delete();
    }

    /**
     * Revoke all Sanctum tokens globally
     */
    private function revokeAllSanctumTokens(User $employee): void
    {
        PersonalAccessToken::where('tokenable_id', $employee->id)
            ->where('tokenable_type', User::class)
            ->delete();
    }

    /**
     * Revoke Passkey credentials for specific tenant
     */
    private function revokePasskeyCredentials(User $employee, Tenant $tenant): void
    {
        WebauthnCredential::where('user_id', $employee->id)
            ->where('tenant_id', $tenant->id)
            ->delete();
    }

    /**
     * Revoke all Passkey credentials globally
     */
    private function revokeAllPasskeyCredentials(User $employee): void
    {
        WebauthnCredential::where('user_id', $employee->id)->delete();
    }

    /**
     * Kill all sessions for user in tenant
     */
    private function killAllSessions(User $employee, Tenant $tenant): void
    {
        // Delete database sessions
        $this->db->table('sessions')
            ->where('user_id', $employee->id)
            ->delete();

        // Broadcast session kill via WebSocket
        $channel = "tenant.{$tenant->id}.user.{$employee->id}";
        $this->redis->publish($channel, json_encode([
            'type' => 'session_kill',
            'user_id' => $employee->id,
            'tenant_id' => $tenant->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]));

        // Invalidate cache entries
        $cacheKeys = [
            "user:{$employee->id}:session",
            "user:{$employee->id}:tenant:{$tenant->id}",
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }
    }

    /**
     * Kill all global sessions
     */
    private function killAllGlobalSessions(User $employee): void
    {
        $this->db->table('sessions')->where('user_id', $employee->id)->delete();
        cache()->tags(["user:{$employee->id}"])->flush();
    }

    /**
     * Block all devices for user in tenant
     */
    private function blockAllDevices(User $employee, Tenant $tenant): void
    {
        UserDevice::where('user_id', $employee->id)
            ->where('tenant_id', $tenant->id)
            ->update(['is_revoked' => true]);
    }

    /**
     * Block all global devices
     */
    private function blockAllGlobalDevices(User $employee): void
    {
        UserDevice::where('user_id', $employee->id)->update(['is_revoked' => true]);
    }

    /**
     * Set cool-down period (30 days before rejoining)
     */
    private function setCoolDownPeriod(User $employee, Tenant $tenant): void
    {
        $cooldownKey = "employee_cooldown:{$tenant->id}:{$employee->id}";
        $cooldownDays = config('security.insider_threat.cooldown_days', 30);

        $this->redis->setex(
            $cooldownKey,
            $cooldownDays * 86400, // Convert to seconds
            CarbonImmutable::now()->toIso8601String()
        );
    }

    /**
     * Log deprovisioning to audit log
     */
    private function logDeprovisioning(
        User $employee,
        Tenant $tenant,
        User $revokedBy,
        string $reason
    ): void {
        $this->log->channel('audit')->$this->logger->info('Employee access revoked', [
            'employee_id' => $employee->id,
            'employee_email' => $employee->email,
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'revoked_by' => $revokedBy->id,
            'revoked_by_email' => $revokedBy->email,
            'reason' => $reason,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'ip_address' => request()?->ip(),
        ]);
    }
}

// ========================
// DTOs
// ========================

final class DeprovisionResult
{
    public static function success(array $details = []): self
    {
        return new self(success: true, details: $details);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }

    private function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
        public readonly array $details = [],
    ) {}
}
