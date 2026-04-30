<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InternalNotificationService;
use App\Services\NotificationFrequencyLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

final class UserNotificationController extends Controller
{
    public function __construct(
        private readonly InternalNotificationService $notificationService,
        private readonly NotificationFrequencyLimiter $frequencyLimiter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $notifications = $this->notificationService->getUserNotifications($validated['user_id'], $limit, $offset);

        return response()->json([
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $count = $this->notificationService->getUnreadCount($validated['user_id']);

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $success = $this->notificationService->markAsRead($id, $validated['user_id'], $request->header('X-Correlation-ID', ''));

        return response()->json([
            'success' => $success,
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        $count = $this->notificationService->markAllAsRead($validated['user_id'], $request->header('X-Correlation-ID', ''));

        return response()->json([
            'data' => [
                'marked_count' => $count,
            ],
        ]);
    }

    public function react(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'reaction_type' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $reaction = $this->notificationService->reactToNotification(
            $id,
            $validated['user_id'],
            $validated['reaction_type'],
            $validated['metadata'] ?? null,
            $request->header('X-Correlation-ID', '')
        );

        return response()->json([
            'data' => $reaction,
        ], 201);
    }

    public function checkUserFrequency(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
            'notification_type' => 'nullable|string',
        ]);

        $result = $this->frequencyLimiter->canSendToUser(
            $validated['user_id'],
            $validated['tenant_id'],
            $validated['notification_type'] ?? null,
            $request->header('X-Correlation-ID', '')
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    public function getUserStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
        ]);

        $stats = $this->frequencyLimiter->getUserNotificationStats($validated['user_id'], $validated['tenant_id']);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
