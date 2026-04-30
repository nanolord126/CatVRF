<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationCampaign;
use App\Models\InternalAudience;
use App\Models\User;
use App\Models\NotificationLog;
use App\Models\NotificationReaction;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

final readonly class InternalNotificationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly NotificationComplianceService $complianceService,
        private readonly NotificationFrequencyLimiter $frequencyLimiter,
        private readonly AudienceSegmentationService $audienceService,
    ) {}

    /**
     * Send notification campaign to audience
     */
    public function sendCampaign(int $campaignId, string $correlationId = ''): array
    {
        $campaign = NotificationCampaign::with('audience')->findOrFail($campaignId);

        // Check compliance first
        $complianceResult = $this->complianceService->checkCampaignCompliance($campaign, $correlationId);
        if (!$complianceResult['passed']) {
            return [
                'success' => false,
                'message' => 'Campaign failed compliance check',
                'compliance_result' => $complianceResult,
            ];
        }

        // Check frequency limits for tenant
        $frequencyResult = $this->frequencyLimiter->canSendBroadcast($campaign->tenant_id, $correlationId);
        if (!$frequencyResult['passed']) {
            return [
                'success' => false,
                'message' => 'Campaign blocked by frequency limits',
                'frequency_result' => $frequencyResult,
            ];
        }

        // Update campaign status
        $campaign->update([
            'status' => NotificationCampaign::STATUS_SENDING,
            'sent_at' => now(),
        ]);

        // Get audience members
        $audienceMembers = $this->audienceService->getAudienceMembers(
            $campaign->audience_id,
            1000,
            0
        );

        $campaign->update(['total_recipients' => count($audienceMembers)]);

        // Process in batches
        $results = $this->processBatch($campaign, $audienceMembers, $correlationId);

        // Update campaign status
        $campaign->update([
            'status' => NotificationCampaign::STATUS_COMPLETED,
            'sent_count' => $results['sent'],
            'delivered_count' => $results['delivered'],
        ]);

        $this->logAction(
            'notification_campaign_sent',
            'NotificationCampaign',
            $campaign->id,
            [
                'tenant_id' => $campaign->tenant_id,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $results['sent'],
                'delivered_count' => $results['delivered'],
            ],
            $correlationId
        );

        return [
            'success' => true,
            'message' => 'Campaign sent successfully',
            'campaign_id' => $campaign->id,
            'results' => $results,
        ];
    }

    /**
     * Process notification batch
     */
    private function processBatch(NotificationCampaign $campaign, array $members, string $correlationId): array
    {
        $sent = 0;
        $delivered = 0;
        $failed = 0;

        foreach ($members as $member) {
            $userId = $member['id'];

            // Check frequency limits for individual user
            $userFrequencyCheck = $this->frequencyLimiter->canSendToUser(
                $userId,
                $campaign->tenant_id,
                $campaign->type,
                $correlationId
            );

            if (!$userFrequencyCheck['passed']) {
                $failed++;
                continue;
            }

            // Send notification
            $result = $this->sendToUser($campaign, $userId, $correlationId);

            if ($result['success']) {
                $sent++;
                $delivered++;

                // Record for frequency tracking
                $this->frequencyLimiter->recordNotificationSent(
                    $userId,
                    $campaign->tenant_id,
                    $campaign->type,
                    $correlationId
                );
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'delivered' => $delivered,
            'failed' => $failed,
        ];
    }

    /**
     * Send notification to individual user
     */
    private function sendToUser(NotificationCampaign $campaign, int $userId, string $correlationId): array
    {
        try {
            // Create notification log
            $log = NotificationLog::create([
                'tenant_id' => $campaign->tenant_id,
                'user_id' => $userId,
                'channel' => $campaign->channel,
                'event_type' => $campaign->type,
                'recipient' => User::find($userId)?->email ?? 'unknown',
                'status' => 'sent',
                'message_content' => $campaign->message,
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                ],
                'sent_at' => now(),
                'delivered_at' => now(), // In-app notifications are delivered immediately
            ]);

            // Create notification record for user's inbox
            $notification = \App\Models\Notification::create([
                'tenant_id' => $campaign->tenant_id,
                'user_id' => $userId,
                'type' => $campaign->type,
                'title' => $campaign->name,
                'message' => $campaign->message,
                'data' => [
                    'campaign_id' => $campaign->id,
                    'channel' => $campaign->channel,
                ],
                'status' => 'delivered',
                'sent_at' => now(),
                'delivered_at' => now(),
            ]);

            return [
                'success' => true,
                'notification_id' => $notification->id,
                'log_id' => $log->id,
            ];
        } catch (\Exception $e) {
            $this->logger->channel('notifications')->error('Failed to send notification to user', [
                'user_id' => $userId,
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get user's internal notifications
     */
    public function getUserNotifications(int $userId, int $limit = 50, int $offset = 0): array
    {
        return \App\Models\Notification::where('user_id', $userId)
            ->where('tenant_id', auth()->user()?->tenant_id ?? 0)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'status' => $notification->status,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                    'data' => $notification->data,
                ];
            })
            ->toArray();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId, string $correlationId = ''): bool
    {
        $notification = \App\Models\Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        $notification->update(['read_at' => now()]);

        $this->logAction(
            'notification_marked_read',
            'Notification',
            $notification->id,
            ['user_id' => $userId],
            $correlationId
        );

        return true;
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId, string $correlationId = ''): int
    {
        $count = \App\Models\Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->logAction(
            'all_notifications_marked_read',
            'User',
            $userId,
            ['count' => $count],
            $correlationId
        );

        return $count;
    }

    /**
     * Get unread notification count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return \App\Models\Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * React to notification
     */
    public function reactToNotification(int $notificationId, int $userId, string $reactionType, ?array $metadata = null, string $correlationId = ''): NotificationReaction
    {
        $notification = \App\Models\Notification::findOrFail($notificationId);

        // Find corresponding log
        $log = NotificationLog::where('user_id', $userId)
            ->where('event_type', $notification->type)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$log) {
            throw new \Exception('Notification log not found');
        }

        // Create reaction
        $reaction = NotificationReaction::create([
            'notification_log_id' => $log->id,
            'user_id' => $userId,
            'tenant_id' => $notification->tenant_id,
            'reaction_type' => $reactionType,
            'reaction_metadata' => $metadata,
            'reacted_at' => now(),
        ]);

        // Update log
        $log->update([
            'reaction_type' => $reactionType,
            'reacted_at' => now(),
            'reaction_metadata' => $metadata,
        ]);

        // Update campaign stats if this was from a campaign
        if (isset($notification->data['campaign_id'])) {
            $campaign = NotificationCampaign::find($notification->data['campaign_id']);
            if ($campaign) {
                $campaign->increment('reacted_count');
            }
        }

        $this->logAction(
            'notification_reacted',
            'NotificationReaction',
            $reaction->id,
            [
                'notification_id' => $notification->id,
                'reaction_type' => $reactionType,
            ],
            $correlationId
        );

        return $reaction;
    }

    /**
     * Get notification statistics for tenant
     */
    public function getTenantNotificationStats(int $tenantId): array
    {
        $cacheKey = "notifications:stats:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addMinutes(15), function () use ($tenantId) {
            $today = now()->startOfDay();

            return [
                'total_sent_today' => NotificationLog::where('tenant_id', $tenantId)
                    ->where('sent_at', '>=', $today)
                    ->count(),
                'total_delivered_today' => NotificationLog::where('tenant_id', $tenantId)
                    ->where('delivered_at', '>=', $today)
                    ->count(),
                'total_reactions_today' => NotificationReaction::where('tenant_id', $tenantId)
                    ->where('reacted_at', '>=', $today)
                    ->count(),
                'campaigns_active' => NotificationCampaign::where('tenant_id', $tenantId)
                    ->whereIn('status', [NotificationCampaign::STATUS_APPROVED, NotificationCampaign::STATUS_SCHEDULED])
                    ->count(),
                'compliance_pass_rate' => $this->complianceService->getComplianceStatistics($tenantId)['pass_rate'],
            ];
        });
    }
}
