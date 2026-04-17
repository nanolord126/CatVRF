<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\ViewingAppointment;
use App\Domains\RealEstate\Models\PropertyTransaction;
use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Exception;

final readonly class RealEstateNotificationService
{
    private const NOTIFICATION_CHANNELS = ['email', 'sms', 'push', 'websocket'];
    private const MAX_NOTIFICATIONS_PER_HOUR = 20;
    private const NOTIFICATION_COOLDOWN_MINUTES = 5;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}

    public function sendViewingConfirmationNotification(ViewingAppointment $viewing, string $correlationId): array
    {
        $this->fraud->check(
            userId: $viewing->buyer_id,
            operationType: 'notification_viewing_confirmation',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $this->validateNotificationRate($viewing->buyer_id);

        $notificationData = [
            'notification_id' => Str::uuid()->toString(),
            'type' => 'viewing_confirmed',
            'user_id' => $viewing->buyer_id,
            'channels' => ['email', 'push', 'sms'],
            'data' => [
                'viewing_uuid' => $viewing->uuid,
                'property_title' => $viewing->property->title,
                'property_address' => $viewing->property->address,
                'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
                'duration_minutes' => $viewing->duration_minutes,
                'agent_name' => $viewing->agent?->name ?? 'Property Owner',
                'agent_phone' => $viewing->agent?->phone ?? $viewing->property->seller->phone,
                'calendar_link' => $this->generateCalendarLink($viewing),
                'map_link' => $this->generateMapLink($viewing->property),
            ],
            'priority' => 'high',
            'created_at' => now()->toIso8601String(),
        ];

        $results = $this->sendNotification($notificationData, $correlationId);

        Log::channel('audit')->info('Viewing confirmation notification sent', [
            'viewing_id' => $viewing->id,
            'viewing_uuid' => $viewing->uuid,
            'buyer_id' => $viewing->buyer_id,
            'channels' => $notificationData['channels'],
            'results' => $results,
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendViewingReminderNotification(ViewingAppointment $viewing, string $correlationId): array
    {
        $this->fraud->check(
            userId: $viewing->buyer_id,
            operationType: 'notification_viewing_reminder',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $this->validateNotificationRate($viewing->buyer_id);

        $notificationData = [
            'notification_id' => Str::uuid()->toString(),
            'type' => 'viewing_reminder',
            'user_id' => $viewing->buyer_id,
            'channels' => ['push', 'sms'],
            'data' => [
                'viewing_uuid' => $viewing->uuid,
                'property_title' => $viewing->property->title,
                'property_address' => $viewing->property->address,
                'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
                'time_until' => $viewing->scheduled_at->diffForHumans(),
                'agent_name' => $viewing->agent?->name ?? 'Property Owner',
                'cancel_link' => $this->generateCancelLink($viewing),
            ],
            'priority' => 'high',
            'created_at' => now()->toIso8601String(),
        ];

        $results = $this->sendNotification($notificationData, $correlationId);

        Log::channel('audit')->info('Viewing reminder notification sent', [
            'viewing_id' => $viewing->id,
            'viewing_uuid' => $viewing->uuid,
            'buyer_id' => $viewing->buyer_id,
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendPriceChangeNotification(Property $property, float $oldPrice, float $newPrice, string $correlationId): array
    {
        $this->fraud->check(
            userId: $property->seller_id,
            operationType: 'notification_price_change',
            amount: (int) $newPrice,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $watchers = $this->getPropertyWatchers($property->id);

        $results = [];
        foreach ($watchers as $watcher) {
            $this->validateNotificationRate($watcher['user_id']);

            $notificationData = [
                'notification_id' => Str::uuid()->toString(),
                'type' => 'price_change',
                'user_id' => $watcher['user_id'],
                'channels' => ['push', 'email'],
                'data' => [
                    'property_uuid' => $property->uuid,
                    'property_title' => $property->title,
                    'property_address' => $property->address,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'price_diff' => $newPrice - $oldPrice,
                    'price_diff_percent' => (($newPrice - $oldPrice) / $oldPrice) * 100,
                    'is_decrease' => $newPrice < $oldPrice,
                    'property_link' => $this->generatePropertyLink($property),
                ],
                'priority' => $newPrice < $oldPrice ? 'high' : 'normal',
                'created_at' => now()->toIso8601String(),
            ];

            $results[$watcher['user_id']] = $this->sendNotification($notificationData, $correlationId);
        }

        Log::channel('audit')->info('Price change notifications sent', [
            'property_id' => $property->id,
            'property_uuid' => $property->uuid,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'watchers_count' => count($watchers),
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendNewPropertyNotification(Property $property, array $matchedUserIds, string $correlationId): array
    {
        $this->fraud->check(
            userId: $property->seller_id,
            operationType: 'notification_new_property',
            amount: (int) $property->price,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $results = [];
        foreach ($matchedUserIds as $userId) {
            $this->validateNotificationRate($userId);

            $notificationData = [
                'notification_id' => Str::uuid()->toString(),
                'type' => 'new_property_match',
                'user_id' => $userId,
                'channels' => ['push', 'email'],
                'data' => [
                    'property_uuid' => $property->uuid,
                    'property_title' => $property->title,
                    'property_address' => $property->address,
                    'price' => $property->price,
                    'area' => $property->area,
                    'rooms' => $property->rooms,
                    'images' => array_slice($property->images, 0, 3),
                    'property_link' => $this->generatePropertyLink($property),
                    'match_score' => $this->calculateMatchScore($property, $userId),
                ],
                'priority' => 'normal',
                'created_at' => now()->toIso8601String(),
            ];

            $results[$userId] = $this->sendNotification($notificationData, $correlationId);
        }

        Log::channel('audit')->info('New property notifications sent', [
            'property_id' => $property->id,
            'property_uuid' => $property->uuid,
            'matched_users_count' => count($matchedUserIds),
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendTransactionStatusNotification(PropertyTransaction $transaction, string $status, string $correlationId): array
    {
        $this->fraud->check(
            userId: $transaction->buyer_id,
            operationType: 'notification_transaction_status',
            amount: (int) $transaction->amount,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $this->validateNotificationRate($transaction->buyer_id);

        $notificationData = [
            'notification_id' => Str::uuid()->toString(),
            'type' => 'transaction_status',
            'user_id' => $transaction->buyer_id,
            'channels' => ['email', 'push'],
            'data' => [
                'transaction_uuid' => $transaction->uuid,
                'property_title' => $transaction->property->title,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $status,
                'status_message' => $this->getStatusMessage($status),
                'next_steps' => $this->getNextSteps($status),
                'support_link' => $this->generateSupportLink($transaction),
            ],
            'priority' => in_array($status, ['escrow_released', 'escrow_refunded'], true) ? 'high' : 'normal',
            'created_at' => now()->toIso8601String(),
        ];

        $results = $this->sendNotification($notificationData, $correlationId);

        Log::channel('audit')->info('Transaction status notification sent', [
            'transaction_id' => $transaction->id,
            'transaction_uuid' => $transaction->uuid,
            'status' => $status,
            'buyer_id' => $transaction->buyer_id,
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendAgentActivityNotification(int $agentId, string $activityType, array $activityData, string $correlationId): array
    {
        $this->fraud->check(
            userId: $agentId,
            operationType: 'notification_agent_activity',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $notificationData = [
            'notification_id' => Str::uuid()->toString(),
            'type' => 'agent_activity',
            'user_id' => $agentId,
            'channels' => ['push', 'email'],
            'data' => [
                'activity_type' => $activityType,
                'activity_data' => $activityData,
                'dashboard_link' => $this->generateAgentDashboardLink($agentId),
            ],
            'priority' => 'normal',
            'created_at' => now()->toIso8601String(),
        ];

        $results = $this->sendNotification($notificationData, $correlationId);

        Log::channel('audit')->info('Agent activity notification sent', [
            'agent_id' => $agentId,
            'activity_type' => $activityType,
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function sendBulkNotification(array $userIds, string $type, array $data, string $priority = 'normal', string $correlationId): array
    {
        $results = [];
        $batchSize = 100;

        foreach (array_chunk($userIds, $batchSize) as $batch) {
            foreach ($batch as $userId) {
                try {
                    $this->validateNotificationRate($userId);

                    $notificationData = [
                        'notification_id' => Str::uuid()->toString(),
                        'type' => $type,
                        'user_id' => $userId,
                        'channels' => ['push'],
                        'data' => $data,
                        'priority' => $priority,
                        'created_at' => now()->toIso8601String(),
                    ];

                    $results[$userId] = $this->sendNotification($notificationData, $correlationId);
                } catch (Exception $e) {
                    Log::error('Bulk notification failed for user', [
                        'user_id' => $userId,
                        'error' => $e->getMessage(),
                        'correlation_id' => $correlationId,
                    ]);
                    $results[$userId] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        }

        Log::channel('audit')->info('Bulk notifications sent', [
            'total_users' => count($userIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
            'failed' => count(array_filter($results, fn($r) => !($r['success'] ?? false))),
            'type' => $type,
            'correlation_id' => $correlationId,
        ]);

        return $results;
    }

    public function getUserNotificationPreferences(int $userId, string $correlationId): array
    {
        $preferences = DB::table('notification_preferences')
            ->where('user_id', $userId)
            ->first();

        if ($preferences === null) {
            return $this->getDefaultNotificationPreferences();
        }

        return [
            'email_enabled' => (bool) $preferences->email_enabled,
            'sms_enabled' => (bool) $preferences->sms_enabled,
            'push_enabled' => (bool) $preferences->push_enabled,
            'websocket_enabled' => (bool) $preferences->websocket_enabled,
            'categories' => json_decode($preferences->categories, true),
            'quiet_hours_start' => $preferences->quiet_hours_start,
            'quiet_hours_end' => $preferences->quiet_hours_end,
            'updated_at' => $preferences->updated_at,
        ];
    }

    public function updateUserNotificationPreferences(int $userId, array $preferences, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'update_notification_preferences',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        DB::table('notification_preferences')
            ->updateOrInsert(
                ['user_id' => $userId],
                [
                    'email_enabled' => $preferences['email_enabled'] ?? true,
                    'sms_enabled' => $preferences['sms_enabled'] ?? true,
                    'push_enabled' => $preferences['push_enabled'] ?? true,
                    'websocket_enabled' => $preferences['websocket_enabled'] ?? true,
                    'categories' => json_encode($preferences['categories'] ?? []),
                    'quiet_hours_start' => $preferences['quiet_hours_start'] ?? '22:00',
                    'quiet_hours_end' => $preferences['quiet_hours_end'] ?? '08:00',
                    'updated_at' => now(),
                ]
            );

        Log::channel('audit')->info('Notification preferences updated', [
            'user_id' => $userId,
            'preferences' => $preferences,
            'correlation_id' => $correlationId,
        ]);

        return $this->getUserNotificationPreferences($userId, $correlationId);
    }

    private function sendNotification(array $notificationData, string $correlationId): array
    {
        $results = [];

        foreach ($notificationData['channels'] as $channel) {
            try {
                $result = match ($channel) {
                    'email' => $this->sendEmailNotification($notificationData, $correlationId),
                    'sms' => $this->sendSMSNotification($notificationData, $correlationId),
                    'push' => $this->sendPushNotification($notificationData, $correlationId),
                    'websocket' => $this->sendWebSocketNotification($notificationData, $correlationId),
                    default => ['success' => false, 'error' => 'Unknown channel'],
                };

                $results[$channel] = $result;
            } catch (Exception $e) {
                $results[$channel] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        DB::table('notifications')->insert([
            'notification_id' => $notificationData['notification_id'],
            'user_id' => $notificationData['user_id'],
            'type' => $notificationData['type'],
            'channels' => json_encode(array_keys($results)),
            'data' => json_encode($notificationData['data']),
            'priority' => $notificationData['priority'],
            'results' => json_encode($results),
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);

        return $results;
    }

    private function sendEmailNotification(array $notificationData, string $correlationId): array
    {
        Queue::push(new \App\Jobs\SendEmailNotificationJob($notificationData, $correlationId));

        return ['success' => true, 'queued' => true];
    }

    private function sendSMSNotification(array $notificationData, string $correlationId): array
    {
        Queue::push(new \App\Jobs\SendSMSNotificationJob($notificationData, $correlationId));

        return ['success' => true, 'queued' => true];
    }

    private function sendPushNotification(array $notificationData, string $correlationId): array
    {
        Queue::push(new \App\Jobs\SendPushNotificationJob($notificationData, $correlationId));

        return ['success' => true, 'queued' => true];
    }

    private function sendWebSocketNotification(array $notificationData, string $correlationId): array
    {
        broadcast(new \App\Broadcasting\RealEstateNotificationEvent($notificationData));

        return ['success' => true, 'broadcasted' => true];
    }

    private function validateNotificationRate(int $userId): void
    {
        $oneHourAgo = now()->subHour();
        $count = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $oneHourAgo)
            ->count();

        if ($count >= self::MAX_NOTIFICATIONS_PER_HOUR) {
            throw new Exception('Notification rate limit exceeded');
        }

        $fiveMinutesAgo = now()->subMinutes(self::NOTIFICATION_COOLDOWN_MINUTES);
        $lastNotification = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->first();

        if ($lastNotification !== null) {
            throw new Exception('Notification cooldown active');
        }
    }

    private function getPropertyWatchers(int $propertyId): array
    {
        return DB::table('property_watchers')
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    private function generateCalendarLink(ViewingAppointment $viewing): string
    {
        $startTime = $viewing->scheduled_at->format('Ymd\THis');
        $endTime = $viewing->scheduled_at->addMinutes($viewing->duration_minutes)->format('Ymd\THis');

        return sprintf(
            'https://calendar.google.com/calendar/render?action=TEMPLATE&text=%s&dates=%s/%s&details=%s&location=%s',
            urlencode("Viewing: {$viewing->property->title}"),
            $startTime,
            $endTime,
            urlencode("Property viewing at {$viewing->property->address}"),
            urlencode($viewing->property->address)
        );
    }

    private function generateMapLink(Property $property): string
    {
        return sprintf(
            'https://maps.google.com/?q=%s',
            urlencode("{$property->lat},{$property->lon}")
        );
    }

    private function generateCancelLink(ViewingAppointment $viewing): string
    {
        return sprintf(
            '%s/real-state/viewings/%s/cancel',
            config('app.url'),
            $viewing->uuid
        );
    }

    private function generatePropertyLink(Property $property): string
    {
        return sprintf(
            '%s/real-state/properties/%s',
            config('app.url'),
            $property->uuid
        );
    }

    private function generateSupportLink(PropertyTransaction $transaction): string
    {
        return sprintf(
            '%s/support/transactions/%s',
            config('app.url'),
            $transaction->uuid
        );
    }

    private function generateAgentDashboardLink(int $agentId): string
    {
        return sprintf(
            '%s/agent/dashboard',
            config('app.url')
        );
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'escrow_pending' => 'Your deposit has been placed in escrow and is being held securely.',
            'escrow_released' => 'Congratulations! Your deposit has been released and the transaction is proceeding.',
            'escrow_refunded' => 'Your deposit has been refunded to your original payment method.',
            'payment_completed' => 'Payment completed successfully. You will receive confirmation shortly.',
            default => 'Your transaction status has been updated.',
        };
    }

    private function getNextSteps(string $status): array
    {
        return match ($status) {
            'escrow_pending' => [
                'Wait for property verification',
                'Schedule viewing if not done',
                'Prepare for final payment',
            ],
            'escrow_released' => [
                'Complete final payment',
                'Sign documents',
                'Receive keys',
            ],
            'escrow_refunded' => [
                'Check your bank account for refund',
                'Contact support if refund not received within 3-5 business days',
            ],
            default => [],
        };
    }

    private function calculateMatchScore(Property $property, int $userId): float
    {
        return rand(70, 95) / 100;
    }

    private function getDefaultNotificationPreferences(): array
    {
        return [
            'email_enabled' => true,
            'sms_enabled' => true,
            'push_enabled' => true,
            'websocket_enabled' => true,
            'categories' => [
                'viewing_updates' => true,
                'price_changes' => true,
                'transaction_updates' => true,
                'marketing' => false,
            ],
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ];
    }
}
