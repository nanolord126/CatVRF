<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationChannelController;
use App\Http\Controllers\Api\NotificationStatisticsController;
use App\Http\Controllers\Api\NotificationReactionController;
use App\Http\Controllers\Api\InternalTaskController;
use App\Http\Controllers\Api\AudienceController;
use App\Http\Controllers\Api\NotificationCampaignController;
use App\Http\Controllers\Api\UserNotificationController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Notification Channels Management
    Route::prefix('channels')->group(function () {
        Route::get('/', [NotificationChannelController::class, 'index']);
        Route::post('/', [NotificationChannelController::class, 'store']);
        Route::get('{channel}', [NotificationChannelController::class, 'show']);
        Route::put('{channel}', [NotificationChannelController::class, 'update']);
        Route::delete('{channel}', [NotificationChannelController::class, 'destroy']);
        Route::post('{channel}/test', [NotificationChannelController::class, 'test']);
        Route::post('{channel}/enable', [NotificationChannelController::class, 'enable']);
        Route::post('{channel}/disable', [NotificationChannelController::class, 'disable']);
    });

    // Notification Statistics
    Route::prefix('statistics')->group(function () {
        Route::get('/', [NotificationStatisticsController::class, 'index']);
        Route::get('overview', [NotificationStatisticsController::class, 'overview']);
        Route::get('channels/{channel}', [NotificationStatisticsController::class, 'channelStats']);
        Route::get('trends', [NotificationStatisticsController::class, 'trends']);
        Route::get('export', [NotificationStatisticsController::class, 'export']);
    });

    // Notification Reactions & Recommendations
    Route::prefix('reactions')->group(function () {
        Route::post('/', [NotificationReactionController::class, 'store']);
        Route::get('analytics', [NotificationReactionController::class, 'analytics']);
        Route::get('recommendations', [NotificationReactionController::class, 'recommendations']);
        Route::post('predict', [NotificationReactionController::class, 'predict']);
    });

    // Internal Tasks Management
    Route::prefix('internal-tasks')->group(function () {
        Route::get('/', [InternalTaskController::class, 'index']);
        Route::post('/', [InternalTaskController::class, 'store']);
        Route::get('{id}', [InternalTaskController::class, 'show']);
        Route::put('{id}/status', [InternalTaskController::class, 'updateStatus']);
        Route::post('{id}/checkpoints', [InternalTaskController::class, 'addCheckpoint']);
        Route::put('checkpoints/{checkpointId}/complete', [InternalTaskController::class, 'completeCheckpoint']);
        Route::get('overdue', [InternalTaskController::class, 'getOverdue']);
        Route::get('statistics', [InternalTaskController::class, 'getStatistics']);
    });

    // Audience Segmentation
    Route::prefix('audiences')->group(function () {
        Route::get('/', [AudienceController::class, 'index']);
        Route::post('/', [AudienceController::class, 'store']);
        Route::get('{id}', [AudienceController::class, 'show']);
        Route::get('{id}/members', [AudienceController::class, 'getMembers']);
        Route::get('masters', [AudienceController::class, 'getMasters']);
        Route::get('services', [AudienceController::class, 'getServices']);
        Route::post('{id}/validate-ownership', [AudienceController::class, 'validateOwnership']);
    });

    // Notification Campaigns
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [NotificationCampaignController::class, 'index']);
        Route::post('/', [NotificationCampaignController::class, 'store']);
        Route::get('{id}', [NotificationCampaignController::class, 'show']);
        Route::post('{id}/check-compliance', [NotificationCampaignController::class, 'checkCompliance']);
        Route::post('{id}/send', [NotificationCampaignController::class, 'send']);
        Route::post('{id}/approve', [NotificationCampaignController::class, 'approve']);
        Route::post('{id}/reject', [NotificationCampaignController::class, 'reject']);
        Route::get('check-frequency', [NotificationCampaignController::class, 'checkFrequency']);
        Route::get('compliance-stats', [NotificationCampaignController::class, 'getComplianceStats']);
        Route::get('tenant-stats', [NotificationCampaignController::class, 'getTenantStats']);
    });

    // User Notifications
    Route::prefix('user-notifications')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index']);
        Route::get('unread-count', [UserNotificationController::class, 'unreadCount']);
        Route::put('{id}/mark-read', [UserNotificationController::class, 'markAsRead']);
        Route::post('mark-all-read', [UserNotificationController::class, 'markAllAsRead']);
        Route::post('{id}/react', [UserNotificationController::class, 'react']);
        Route::get('check-frequency', [UserNotificationController::class, 'checkUserFrequency']);
        Route::get('stats', [UserNotificationController::class, 'getUserStats']);
    });
});
