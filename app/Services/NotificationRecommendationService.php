<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationReaction;
use App\Models\User;
use App\Traits\WithAuditLogging;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

final readonly class NotificationRecommendationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
        private readonly NotificationAnalyticsService $analyticsService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Get recommended notification channels for a user
     */
    public function getRecommendedChannels(int $userId, int $tenantId): array
    {
        $cacheKey = "notification_recommendation:channels:{$userId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(6), function () use ($userId, $tenantId) {
            $userEngagementScore = $this->analyticsService->getUserEngagementScore($userId, $tenantId);
            $reactionStatsByChannel = $this->analyticsService->getReactionStatsByChannel($tenantId);

            // Get user's personal reaction history by channel
            $userChannelStats = NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.user_id', $userId)
                ->where('notification_reactions.tenant_id', $tenantId)
                ->selectRaw('notification_logs.channel, 
                    SUM(CASE WHEN notification_reactions.reaction_type IN (?, ?) THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN notification_reactions.reaction_type IN (?, ?, ?) THEN 1 ELSE 0 END) as negative',
                    [NotificationReaction::REACTION_LIKE, NotificationReaction::REACTION_HELPFUL,
                        NotificationReaction::REACTION_DISLIKE, NotificationReaction::REACTION_NOT_HELPFUL,
                        NotificationReaction::REACTION_REPORTED])
                ->groupBy('notification_logs.channel')
                ->get()
                ->keyBy('channel');

            $channels = ['email', 'push', 'sms', 'telegram', 'in_app'];
            $recommendations = [];

            foreach ($channels as $channel) {
                $score = 50.0; // Base score

                // Adjust based on tenant-wide performance
                if (isset($reactionStatsByChannel[$channel])) {
                    $score += min(20, $reactionStatsByChannel[$channel] / 10);
                }

                // Adjust based on user's personal history
                if ($userChannelStats->has($channel)) {
                    $stats = $userChannelStats[$channel];
                    $total = $stats->positive + $stats->negative;
                    if ($total > 0) {
                        $userScore = (($stats->positive - $stats->negative) / $total) * 100;
                        $score = ($score * 0.3) + ($userScore * 0.7); // Weight user history higher
                    }
                }

                // Adjust based on overall engagement
                if ($userEngagementScore < 30) {
                    // Low engagement users prefer less intrusive channels
                    if (in_array($channel, ['email', 'in_app'])) {
                        $score += 10;
                    } else {
                        $score -= 10;
                    }
                }

                $recommendations[$channel] = [
                    'channel' => $channel,
                    'score' => round(max(0, min(100, $score)), 2),
                    'reason' => $this->getRecommendationReason($channel, $score, $userChannelStats->has($channel)),
                ];
            }

            // Sort by score descending
            uasort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);

            return $recommendations;
        });
    }

    /**
     * Get recommended notification types for a user
     */
    public function getRecommendedTypes(int $userId, int $tenantId, int $limit = 10): array
    {
        $cacheKey = "notification_recommendation:types:{$userId}:{$tenantId}:{$limit}";

        return $this->cache->remember($cacheKey, now()->addHours(6), function () use ($userId, $tenantId, $limit) {
            // Get user's positive reactions by type
            $userPositiveTypes = NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.user_id', $userId)
                ->where('notification_reactions.tenant_id', $tenantId)
                ->whereIn('notification_reactions.reaction_type', [
                    NotificationReaction::REACTION_LIKE,
                    NotificationReaction::REACTION_HELPFUL,
                ])
                ->selectRaw('notification_logs.event_type, COUNT(*) as count')
                ->groupBy('notification_logs.event_type')
                ->orderByDesc('count')
                ->limit($limit)
                ->pluck('count', 'event_type')
                ->toArray();

            // Get tenant-wide top performing types
            $topTenantTypes = $this->analyticsService->getTopPerformingTypes($tenantId, $limit * 2);

            // Combine and score
            $recommendations = [];
            $allTypes = array_unique(array_merge(array_keys($userPositiveTypes), array_keys($topTenantTypes)));

            foreach ($allTypes as $type) {
                $userScore = $userPositiveTypes[$type] ?? 0;
                $tenantScore = $topTenantTypes[$type] ?? 0;

                // Weight user history higher (70%) than tenant-wide (30%)
                $score = ($userScore * 0.7) + ($tenantScore * 0.3);

                $recommendations[$type] = [
                    'type' => $type,
                    'score' => $score,
                    'user_interactions' => $userScore,
                    'tenant_performance' => $tenantScore,
                ];
            }

            // Sort by score descending and limit
            uasort($recommendations, fn ($a, $b) => $b['score'] <=> $a['score']);
            return array_slice($recommendations, 0, $limit, true);
        });
    }

    /**
     * Get optimal send time for a user
     */
    public function getOptimalSendTime(int $userId, int $tenantId): array
    {
        $cacheKey = "notification_recommendation:optimal_time:{$userId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(12), function () use ($userId, $tenantId) {
            $reactionTimes = NotificationReaction::query()
                ->join('notification_logs', 'notification_reactions.notification_log_id', '=', 'notification_logs.id')
                ->where('notification_reactions.user_id', $userId)
                ->where('notification_reactions.tenant_id', $tenantId)
                ->whereIn('notification_reactions.reaction_type', [
                    NotificationReaction::REACTION_LIKE,
                    NotificationReaction::REACTION_HELPFUL,
                ])
                ->selectRaw('HOUR(notification_logs.sent_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->orderByDesc('count')
                ->get();

            if ($reactionTimes->isEmpty()) {
                // Default to business hours if no data
                return [
                    'hour' => 10,
                    'confidence' => 0.0,
                    'reason' => 'insufficient_data',
                ];
            }

            $bestHour = $reactionTimes->first()->hour;
            $totalReactions = $reactionTimes->sum('count');
            $confidence = min(1.0, $totalReactions / 10); // Need at least 10 reactions for high confidence

            return [
                'hour' => $bestHour,
                'confidence' => round($confidence, 2),
                'reason' => $confidence > 0.7 ? 'high_confidence' : 'low_confidence',
            ];
        });
    }

    /**
     * Predict if user will react positively to notification
     */
    public function predictPositiveReaction(
        int $userId,
        int $tenantId,
        string $channel,
        string $notificationType
    ): array {
        $engagementScore = $this->analyticsService->getUserEngagementScore($userId, $tenantId);
        $recommendedChannels = $this->getRecommendedChannels($userId, $tenantId);
        $recommendedTypes = $this->getRecommendedTypes($userId, $tenantId, 20);

        $channelScore = $recommendedChannels[$channel]['score'] ?? 50;
        $typeScore = $recommendedTypes[$notificationType]['score'] ?? 0;

        // Weight factors
        $engagementWeight = 0.3;
        $channelWeight = 0.4;
        $typeWeight = 0.3;

        $probability = ($engagementScore * $engagementWeight) +
                       ($channelScore * $channelWeight) +
                       (min(100, $typeScore) * $typeWeight);

        $probability = round(max(0, min(100, $probability)), 2);

        return [
            'probability' => $probability,
            'confidence' => $this->calculatePredictionConfidence($userId, $tenantId),
            'recommendation' => $probability > 60 ? 'send' : ($probability > 40 ? 'maybe' : 'avoid'),
            'factors' => [
                'engagement_score' => $engagementScore,
                'channel_score' => $channelScore,
                'type_score' => min(100, $typeScore),
            ],
        ];
    }

    /**
     * Get personalization suggestions for notification content
     */
    public function getPersonalizationSuggestions(int $userId, int $tenantId): array
    {
        $cacheKey = "notification_recommendation:personalization:{$userId}:{$tenantId}";

        return $this->cache->remember($cacheKey, now()->addHours(4), function () use ($userId, $tenantId) {
            $user = User::find($userId);
            if (!$user) {
                return [];
            }

            $suggestions = [];

            // Time-based personalization
            $optimalTime = $this->getOptimalSendTime($userId, $tenantId);
            if ($optimalTime['confidence'] > 0.5) {
                $suggestions['optimal_send_hour'] = $optimalTime['hour'];
            }

            // Channel preferences
            $recommendedChannels = $this->getRecommendedChannels($userId, $tenantId);
            $topChannel = array_key_first($recommendedChannels);
            if ($recommendedChannels[$topChannel]['score'] > 70) {
                $suggestions['preferred_channel'] = $topChannel;
            }

            // Engagement level
            $engagementScore = $this->analyticsService->getUserEngagementScore($userId, $tenantId);
            $suggestions['engagement_level'] = match (true) {
                $engagementScore > 70 => 'high',
                $engagementScore > 40 => 'medium',
                default => 'low',
            };

            // Frequency suggestion
            $suggestions['frequency_suggestion'] = match ($suggestions['engagement_level']) {
                'high' => 'can_send_frequently',
                'medium' => 'moderate_frequency',
                'low' => 'send_sparsely',
            };

            return $suggestions;
        });
    }

    /**
     * Batch get recommendations for multiple users
     */
    public function batchGetRecommendations(array $userIds, int $tenantId): array
    {
        $recommendations = [];

        foreach ($userIds as $userId) {
            $recommendations[$userId] = [
                'channels' => $this->getRecommendedChannels($userId, $tenantId),
                'types' => $this->getRecommendedTypes($userId, $tenantId, 5),
                'optimal_time' => $this->getOptimalSendTime($userId, $tenantId),
                'personalization' => $this->getPersonalizationSuggestions($userId, $tenantId),
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate prediction confidence based on data availability
     */
    private function calculatePredictionConfidence(int $userId, int $tenantId): float
    {
        $reactionCount = NotificationReaction::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->count();

        if ($reactionCount < 5) {
            return 0.2; // Very low confidence
        } elseif ($reactionCount < 10) {
            return 0.5; // Low confidence
        } elseif ($reactionCount < 20) {
            return 0.7; // Medium confidence
        } else {
            return 0.9; // High confidence
        }
    }

    /**
     * Get human-readable recommendation reason
     */
    private function getRecommendationReason(string $channel, float $score, bool $hasUserData): string
    {
        if (!$hasUserData) {
            return 'Based on tenant-wide performance';
        }

        if ($score > 80) {
            return 'User strongly prefers this channel';
        } elseif ($score > 60) {
            return 'User prefers this channel';
        } elseif ($score > 40) {
            return 'Moderate preference';
        } else {
            return 'User dislikes this channel';
        }
    }

    /**
     * Train/retrain recommendation model (placeholder for ML integration)
     */
    public function trainModel(int $tenantId, string $correlationId = ''): void
    {
        $this->logger->channel('notifications')->info('Training notification recommendation model', [
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        // Invalidate all recommendation caches for this tenant
        $this->cache->tags(["notification_recommendation:tenant:{$tenantId}"])->flush();

        // In production, this would:
        // 1. Collect reaction data
        // 2. Extract features (channel, type, time, user attributes)
        // 3. Train ML model (XGBoost, LightGBM, or neural network)
        // 4. Evaluate model performance
        // 5. Deploy model to inference endpoint

        $this->logAction(
            'notification_model_trained',
            'NotificationRecommendationService',
            $tenantId,
            ['tenant_id' => $tenantId],
            $correlationId
        );
    }
}
