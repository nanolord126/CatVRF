<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use Psr\Log\LoggerInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * In-App Notification Channel — сохраняет уведомления в БД для показа в UI.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Поддерживает:
 * - Сохранение в таблицу notifications
 * - Real-time broadcast через Laravel Echo
 * - Auto-close timeout
 * - Максимум N непрочитанных (чистка старых)
 * - Tenant-scoping
 * - Audit-лог + correlation_id
 */
final class InAppChannel
{
    /**
     * Конструктор
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Отправить in-app уведомление.
     *
     * Объект $notification должен реализовать метод toInApp(),
     * возвращающий массив: ['title' => ..., 'message' => ..., 'type' => ..., 'icon' => ...,
     *                       'action_url' => ..., 'action_label' => ..., 'auto_close' => bool]
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toInApp') && !method_exists($notification, 'toDatabase')) {
            $this->logger->warning('Notification does not have toInApp or toDatabase method', [
                'notification_class' => get_class($notification),
                'notifiable_id' => $notifiable->id ?? null,
            ]);
            return;
        }

        try {
            $data = method_exists($notification, 'toInApp')
                ? $notification->toInApp()
                : $notification->toDatabase();

            $userId = $notifiable->id ?? null;
            $tenantId = method_exists($notification, 'getTenantId')
                ? $notification->getTenantId()
                : null;
            $correlationId = method_exists($notification, 'getCorrelationId')
                ? $notification->getCorrelationId()
                : Str::uuid()->toString();

            $this->db->table('notifications')->insert([
                'id'              => Str::uuid()->toString(),
                'type'            => get_class($notification),
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $userId,
                'data'            => json_encode(array_merge($data, [
                    'correlation_id' => $correlationId,
                    'tenant_id'      => $tenantId,
                ]), JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Очистка старых непрочитанных, если превышен лимит
            $this->cleanupOldUnread($userId);

            // Broadcast через Echo (если доступен)
            $this->broadcastIfAvailable($userId, $data, $correlationId);

            $this->logger->info('In-app notification created', [
                'type'           => method_exists($notification, 'getType') ? $notification->getType() : get_class($notification),
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'tenant_id'      => $tenantId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('In-app notification failed', [
                'notification_class' => get_class($notification),
                'error'              => $e->getMessage(),
                'correlation_id'     => method_exists($notification, 'getCorrelationId')
                    ? $notification->getCorrelationId()
                    : null,
            ]);

            throw $e;
        }
    }

    /**
     * Прямая вставка in-app уведомления (без Notification-объекта).
     *
     * Используется NotificationChannelService для произвольных уведомлений.
     */
    public function sendDirect(
        int     $userId,
        string  $title,
        string  $message,
        string  $type = 'info',
        ?string $correlationId = null,
        ?int    $tenantId = null,
        ?string $actionUrl = null,
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->db->table('notifications')->insert([
            'id'              => Str::uuid()->toString(),
            'type'            => 'App\\Notifications\\DirectInAppNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id'   => $userId,
            'data'            => json_encode([
                'title'          => $title,
                'message'        => $message,
                'type'           => $type,
                'action_url'     => $actionUrl,
                'correlation_id' => $correlationId,
                'tenant_id'      => $tenantId,
            ], JSON_UNESCAPED_UNICODE),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->cleanupOldUnread($userId);
        $this->broadcastIfAvailable($userId, [
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
        ], $correlationId);

        $this->logger->info('In-app direct notification created', [
            'user_id'        => $userId,
            'type'           => $type,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Удаляем старые непрочитанные, если превышен лимит.
     */
    private function cleanupOldUnread(?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $maxUnread = (int) config('notifications.channels.in_app.max_unread', 200);

        $count = $this->db->table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereNull('read_at')
            ->count();

        if ($count > $maxUnread) {
            $deleteCount = $count - $maxUnread;
            $oldIds = $this->db->table('notifications')
                ->where('notifiable_id', $userId)
                ->where('notifiable_type', 'App\\Models\\User')
                ->whereNull('read_at')
                ->orderBy('created_at', 'asc')
                ->limit($deleteCount)
                ->pluck('id');

            $this->db->table('notifications')
                ->whereIn('id', $oldIds)
                ->delete();
        }
    }

    /**
     * Broadcast real-time event через Laravel Echo.
     */
    private function broadcastIfAvailable(
        ?int    $userId,
        array   $data,
        string  $correlationId,
    ): void {
        if ($userId === null) {
            return;
        }

        try {
            if (class_exists(\App\Events\InAppNotificationCreated::class)) {
                event(new \App\Events\InAppNotificationCreated(
                    userId: $userId,
                    data: $data,
                    correlationId: $correlationId,
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->debug('In-app broadcast skipped', [
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
