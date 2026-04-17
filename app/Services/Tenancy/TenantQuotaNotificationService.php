<?php declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Log\LogManager;

/**
 * Tenant Quota Notification Service
 *
 * Production 2026 CANON - Quota Threshold Alerts
 *
 * Sends notifications when tenants approach their quota limits:
 * - 75% usage: Warning notification
 * - 90% usage: Critical notification
 * - 100% usage: Block notification (handled by exception)
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final readonly class TenantQuotaNotificationService
{
    private const THRESHOLD_WARNING = 75;
    private const THRESHOLD_CRITICAL = 90;

    public function __construct(
        private readonly TenantResourceLimiterService $quotaService,
        private readonly QueueFactory $queue,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    /**
     * Check and send quota threshold notifications
     * Should be called by scheduled job (e.g., every hour)
     */
    public function checkThresholds(): int
    {
        $notificationsSent = 0;
        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            $notificationsSent += $this->checkTenantThresholds($tenant);
        }

        $this->logger->info('Quota threshold check completed', [
            'tenants_checked' => $tenants->count(),
            'notifications_sent' => $notificationsSent,
        ]);

        return $notificationsSent;
    }

    /**
     * Check thresholds for a specific tenant
     */
    private function checkTenantThresholds(Tenant $tenant): int
    {
        $sent = 0;
        $stats = $this->quotaService->getQuotaStats((int) $tenant->id);

        foreach ($stats as $resource => $data) {
            if ($data['percentage'] >= self::THRESHOLD_CRITICAL) {
                $this->sendCriticalNotification($tenant, $resource, $data);
                $sent++;
            } elseif ($data['percentage'] >= self::THRESHOLD_WARNING) {
                $this->sendWarningNotification($tenant, $resource, $data);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send warning notification (75% threshold)
     */
    private function sendWarningNotification(Tenant $tenant, string $resource, array $data): void
    {
        $this->queue->connection()->push(
            new \App\Jobs\SendQuotaWarningJob(
                $tenant->id,
                $resource,
                $data
            )
        );

        $this->logger->info('Quota warning notification queued', [
            'tenant_id' => $tenant->id,
            'resource' => $resource,
            'percentage' => $data['percentage'],
        ]);
    }

    /**
     * Send critical notification (90% threshold)
     */
    private function sendCriticalNotification(Tenant $tenant, string $resource, array $data): void
    {
        $this->queue->connection()->push(
            new \App\Jobs\SendQuotaCriticalJob(
                $tenant->id,
                $resource,
                $data
            )
        );

        $this->logger->warning('Quota critical notification queued', [
            'tenant_id' => $tenant->id,
            'resource' => $resource,
            'percentage' => $data['percentage'],
        ]);
    }

    /**
     * Send immediate notification for quota exceeded
     */
    public function notifyQuotaExceeded(int $tenantId, string $resourceType, array $quotaData): void
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return;
        }

        // Notify tenant owners/managers
        $recipients = $this->getTenantNotificationRecipients($tenantId);

        foreach ($recipients as $recipient) {
            $this->queue->connection()->push(
                new \App\Jobs\SendQuotaExceededJob(
                    $recipient->id,
                    $tenantId,
                    $resourceType,
                    $quotaData
                )
            );
        }

        $this->logger->warning('Quota exceeded notifications sent', [
            'tenant_id' => $tenantId,
            'resource_type' => $resourceType,
            'recipients_count' => count($recipients),
        ]);
    }

    /**
     * Get tenant notification recipients (owners and managers)
     */
    private function getTenantNotificationRecipients(int $tenantId): array
    {
        return User::where('tenant_id', $tenantId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Manager', 'Admin']);
            })
            ->get()
            ->toArray();
    }
}
