<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * User Analytics Service
 *
 * Handles user-specific analytics operations including GDPR compliance.
 * Follows Single Responsibility Principle - only handles user analytics.
 */
final readonly class UserAnalyticsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * GDPR: Delete all analytics data for a user.
     * Called when user requests data deletion (right to be forgotten).
     */
    public function deleteUserAnalytics(int $userId, int $tenantId): void
    {
        // Fraud check before deletion
        // TODO: Integrate with FraudDetectionService when available

        $this->db->transaction(function () use ($userId, $tenantId) {
            // Delete from analytics_events
            $this->db->table('analytics_events')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->delete();

            // Anonymize in analytics_user_metrics (keep aggregated data, remove user_id)
            $this->db->table('analytics_user_metrics')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->update(['user_id' => null]);

            // Log the deletion for audit
            $this->logAction(
                action: 'user_analytics_deleted',
                entityType: 'UserAnalytics',
                entityId: $userId,
                context: [
                    'tenant_id' => $tenantId,
                ],
                userId: $userId,
                tenantId: $tenantId
            );

            $this->invalidateMetricsCache($tenantId);
        });
    }

    /**
     * Invalidate metrics cache for a tenant.
     */
    private function invalidateMetricsCache(int $tenantId): void
    {
        $this->cache->tags(["analytics:{$tenantId}"])->flush();
    }
}
