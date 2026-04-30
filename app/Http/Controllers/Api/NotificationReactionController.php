<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationAnalyticsService;
use App\Services\NotificationRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class NotificationReactionController extends Controller
{
    public function __construct(
        private readonly NotificationAnalyticsService $analyticsService,
        private readonly NotificationRecommendationService $recommendationService,
    ) {}

    /**
     * Track user reaction to a notification
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_log_id' => 'required|integer|exists:notification_logs,id',
            'reaction_type' => 'required|in:like,dislike,neutral,helpful,not_helpful,reported',
            'metadata' => 'nullable|array',
        ]);

        $reaction = $this->analyticsService->trackReaction(
            notificationLogId: $validated['notification_log_id'],
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id ?? 0,
            reactionType: $validated['reaction_type'],
            metadata: $validated['metadata'] ?? null,
            correlationId: $request->header('X-Correlation-ID') ?? '',
        );

        return response()->json([
            'success' => true,
            'reaction' => $reaction,
        ]);
    }

    /**
     * Get notification analytics for current user
     */
    public function analytics(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $tenantId = auth()->user()?->tenant_id ?? 0;

        $period = null;
        if ($request->has('from') && $request->has('to')) {
            $period = [
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ];
        }

        return response()->json([
            'stats' => $this->analyticsService->getReactionStats($tenantId, $period),
            'stats_by_channel' => $this->analyticsService->getReactionStatsByChannel($tenantId, $period),
            'stats_by_type' => $this->analyticsService->getReactionStatsByType($tenantId, $period),
            'user_trends' => $this->analyticsService->getUserReactionTrends($userId, $tenantId),
            'engagement_score' => $this->analyticsService->getUserEngagementScore($userId, $tenantId),
        ]);
    }

    /**
     * Get personalized recommendations for current user
     */
    public function recommendations(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $tenantId = auth()->user()?->tenant_id ?? 0;

        return response()->json([
            'channels' => $this->recommendationService->getRecommendedChannels($userId, $tenantId),
            'types' => $this->recommendationService->getRecommendedTypes($userId, $tenantId),
            'optimal_time' => $this->recommendationService->getOptimalSendTime($userId, $tenantId),
            'personalization' => $this->recommendationService->getPersonalizationSuggestions($userId, $tenantId),
        ]);
    }

    /**
     * Predict reaction probability for a notification
     */
    public function predict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string',
            'notification_type' => 'required|string',
        ]);

        $prediction = $this->recommendationService->predictPositiveReaction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id ?? 0,
            channel: $validated['channel'],
            notificationType: $validated['notification_type'],
        );

        return response()->json([
            'prediction' => $prediction,
        ]);
    }
}
