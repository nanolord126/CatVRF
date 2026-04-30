<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationReaction;
use App\Traits\WithAuditLogging;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

final readonly class NotificationAnalyticsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Track user reaction to a notification
     */
    public function trackReaction(
        int $notificationLogId,
        int $userId,
        int $tenantId,
        string $reactionType,
        ?array $metadata = null,
        string $correlationId = ''
    ): NotificationReaction {
        return $this->db->transaction(function () use (
            $notificationLogId,
            $userId,
            $tenantId,
            $reactionType,
            $metadata,
            $correlationId
        ) {
            // Update notification log with reaction
            $notificationLog = NotificationLog::findOrFail($notificationLogId);
            $notificationLog->update([
                'reaction_type' => $reactionType,
                'reacted_at' => now(),
                'reaction_metadata' => $metadata,
            ]);

            // Create reaction record
            $reaction = NotificationReaction::create([
                'notification_log_id' => $notificationLogId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'reaction_type' => $reactionType,
                'reaction_metadata' => $metadata,
                'reacted_at' => now(),
            ]);

            // Invalidate cache
            $this->invalidateAnalyticsCache($tenantId, $userId);

            // Log audit
            $this->logAction(
                'notification_reaction',
                'NotificationReaction',
                $reaction->id,
                [
                    'notification_log_id' => $notificationLogId,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                    'reaction_type' => $reactionType,
                ],
                $correlationId
            );

            $this->logger->channel('notifications')->info('Notification reaction tracked', [
                'notification_log_id' => $notificationLogId,
                'user_id' => $userId,
                'reaction_type' => $reactionType,
                'correlation_id' => $correlationId,
            ]);

            return $reaction;
        });
    }

    /**
     * Get reaction statistics for a tenant
     */
    public function getReactionStats(int $tenantId, ?string $period = null): array
    {
        $cacheKey = "notification_analytics:stats:{$tenantId}:{$period}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $period) {
            $query = NotificationReaction::where('tenant_id', $tenantId);

            if ($period) {
                $query->forPeriod($period['from'], $period['to']);
            }

            return [
                'total_reactions' => $query->count(),
                'positive_reactions' => (clone $query)->positive()->count(),
                'negative_reactions' => (clone $query)->negative()->count(),
                'by_type' => $query->selectRaw('reaction_type, COUNT(*) as count')
                    ->groupBy('reaction_type')
                    ->pluck('count', 'reaction_type')
                    ->toArray(),
                'reaction_rate' => $this->calculateReactionRate($tenantId, $period),
            ];
        });
    }

    /**
     * Get reaction statistics by channel
     */
    public function getReactionStatsByChannel(int $tenantId, ?string $period = null): array
    {
        $cacheKey = "notification_analytics:stats_by_channel:{$tenantId}:{$period}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $period) {
            $query = NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.tenant_id', $tenantId);

            if ($period) {
                $query->whereBetween('notification_reactions.reacted_at', [$period['from'], $period['to']]);
            }

            return $query->selectRaw('notification_logs.channel, COUNT(*) as count')
                ->groupBy('notification_logs.channel')
                ->pluck('count', 'channel')
                ->toArray();
        });
    }

    /**
     * Get reaction statistics by notification type
     */
    public function getReactionStatsByType(int $tenantId, ?string $period = null): array
    {
        $cacheKey = "notification_analytics:stats_by_type:{$tenantId}:{$period}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId, $period) {
            $query = NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.tenant_id', $tenantId);

            if ($period) {
                $query->whereBetween('notification_reactions.reacted_at', [$period['from'], $period['to']]);
            }

            return $query->selectRaw('notification_logs.event_type, COUNT(*) as count')
                ->groupBy('notification_logs.event_type')
                ->pluck('count', 'event_type')
                ->toArray();
        });
    }

    /**
     * Get user reaction trends
     */
    public function getUserReactionTrends(int $userId, int $tenantId, int $days = 30): array
    {
        $cacheKey = "notification_analytics:user_trends:{$userId}:{$tenantId}:{$days}";

        return $this->cache->remember($cacheKey, now()->addMinutes(30), function () use ($userId, $tenantId, $days) {
            return NotificationReaction::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->where('reacted_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(reacted_at) as date, reaction_type, COUNT(*) as count')
                ->groupBy('date', 'reaction_type')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->map(function ($day) {
                    return $day->pluck('count', 'reaction_type')->toArray();
                })
                ->toArray();
        });
    }

    /**
     * Calculate reaction rate (reactions / delivered notifications)
     */
    private function calculateReactionRate(int $tenantId, ?array $period = null): float
    {
        $deliveredQuery = NotificationLog::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'delivered']);

        if ($period) {
            $deliveredQuery->whereBetween('created_at', [$period['from'], $period['to']]);
        }

        $deliveredCount = $deliveredQuery->count();

        if ($deliveredCount === 0) {
            return 0.0;
        }

        $reactionQuery = NotificationReaction::where('tenant_id', $tenantId);

        if ($period) {
            $reactionQuery->whereBetween('reacted_at', [$period['from'], $period['to']]);
        }

        $reactionCount = $reactionQuery->count();

        return round(($reactionCount / $deliveredCount) * 100, 2);
    }

    /**
     * Get top performing notification types
     */
    public function getTopPerformingTypes(int $tenantId, int $limit = 10): array
    {
        $cacheKey = "notification_analytics:top_types:{$tenantId}:{$limit}";

        return $this->cache->remember($cacheKey, now()->addHours(1), function () use ($tenantId, $limit) {
            return NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.tenant_id', $tenantId)
                ->where('notification_reactions.reaction_type', NotificationReaction::REACTION_HELPFUL)
                ->selectRaw('notification_logs.event_type, COUNT(*) as count')
                ->groupBy('notification_logs.event_type')
                ->orderByDesc('count')
                ->limit($limit)
                ->pluck('count', 'event_type')
                ->toArray();
        });
    }

    /**
     * Get user engagement score based on reactions
     */
    public function getUserEngagementScore(int $userId, int $tenantId): float
    {
        $cacheKey = "notification_analytics:engagement_score:{$userId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(2), function () use ($userId, $tenantId) {
            $reactions = NotificationReaction::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->get();

            if ($reactions->isEmpty()) {
                return 0.0;
            }

            $positiveCount = $reactions->filter(fn ($r) => in_array($r->reaction_type, [
                NotificationReaction::REACTION_LIKE,
                NotificationReaction::REACTION_HELPFUL,
            ]))->count();

            $negativeCount = $reactions->filter(fn ($r) => in_array($r->reaction_type, [
                NotificationReaction::REACTION_DISLIKE,
                NotificationReaction::REACTION_NOT_HELPFUL,
                NotificationReaction::REACTION_REPORTED,
            ]))->count();

            $total = $reactions->count();
            $score = (($positiveCount - $negativeCount) / $total) * 100;

            return round(max(0, min(100, $score)), 2);
        });
    }

    /**
     * Invalidate analytics cache for tenant/user
     */
    private function invalidateAnalyticsCache(int $tenantId, ?int $userId = null): void
    {
        $this->cache->tags(["notification_analytics:{$tenantId}"])->flush();

        if ($userId) {
            $this->cache->tags(["notification_analytics:user:{$userId}"])->flush();
        }
    }
}
