<?php declare(strict_types=1);

namespace App\Notifications\Channels;

use Psr\Log\LoggerInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use App\Domains\Education\Channels\Models\BusinessChannel;
use App\Domains\Education\Channels\Models\ChannelSubscriber;

/**
 * Marketplace Channel — отправляет уведомления через внутренние каналы (паблики) маркетплейса.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Каждый бизнес (tenant) имеет свой BusinessChannel — аналог публичного
 * канала, на который подписываются пользователи. Через этот канал
 * уведомления доставляются подписчикам в виде постов / in-app нотификаций.
 *
 * Поддерживает:
 * - Публикация поста в канал бизнеса (broadcast всем подписчикам)
 * - Прямое уведомление конкретному подписчику
 * - Audit-лог + correlation_id
 * - Tenant-scoping
 * - Автоматическая доставка через InAppChannel подписчикам
 */
final class MarketplaceChannel
{
    /**
     * Конструктор — constructor injection по канону.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly InAppChannel $inAppChannel,
    ) {}

    /**
     * Отправить уведомление через marketplace-канал бизнеса.
     *
     * Объект $notification должен реализовать метод toMarketplace(),
     * возвращающий массив:
     *   ['title' => ..., 'content' => ..., 'tenant_id' => ..., 'is_promo' => false]
     *
     * Уведомление публикуется как пост в BusinessChannel тенанта
     * и рассылается in-app всем активным подписчикам.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toMarketplace')) {
            $this->logger->warning('Notification does not have toMarketplace method', [
                'notification_class' => get_class($notification),
                'notifiable_id'      => $notifiable->id ?? null,
            ]);
            return;
        }

        try {
            $data = $notification->toMarketplace();

            $tenantId = $data['tenant_id']
                ?? (method_exists($notification, 'getTenantId') ? $notification->getTenantId() : null);

            if ($tenantId === null) {
                throw new \RuntimeException(
                    'Marketplace channel requires tenant_id in notification data'
                );
            }

            $correlationId = method_exists($notification, 'getCorrelationId')
                ? $notification->getCorrelationId()
                : Str::uuid()->toString();

            // Находим канал бизнеса
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->first();

            if ($channel === null) {
                $this->logger->info('No active marketplace channel for tenant, skipping', [
                    'tenant_id'      => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
                return;
            }

            // Публикуем пост в канал
            $postId = $this->createChannelPost($channel, $data, $correlationId, $tenantId);

            // Рассылаем in-app уведомления подписчикам
            $subscriberCount = $this->notifySubscribers($channel, $data, $correlationId);

            $this->logger->info('Marketplace channel notification sent', [
                'type'             => method_exists($notification, 'getType') ? $notification->getType() : get_class($notification),
                'tenant_id'        => $tenantId,
                'channel_id'       => $channel->id,
                'post_id'          => $postId,
                'subscribers_sent' => $subscriberCount,
                'correlation_id'   => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Marketplace channel notification failed', [
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
     * Прямая отправка уведомления в канал маркетплейса (без Notification-объекта).
     *
     * Создаёт пост в канале тенанта и рассылает in-app подписчикам.
     */
    public function sendDirect(
        int     $tenantId,
        string  $title,
        string  $message,
        bool    $isPromo = false,
        ?string $correlationId = null,
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $channel = BusinessChannel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if ($channel === null) {
            $this->logger->debug('No marketplace channel for tenant, skipping direct send', [
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);
            return;
        }

        $data = [
            'title'    => $title,
            'content'  => $message,
            'is_promo' => $isPromo,
        ];

        $postId = $this->createChannelPost($channel, $data, $correlationId, $tenantId);
        $subscriberCount = $this->notifySubscribers($channel, $data, $correlationId);

        $this->logger->info('Marketplace direct notification sent', [
            'tenant_id'        => $tenantId,
            'channel_id'       => $channel->id,
            'post_id'          => $postId,
            'subscribers_sent' => $subscriberCount,
            'correlation_id'   => $correlationId,
        ]);
    }

    /**
     * Отправить уведомление конкретному подписчику канала.
     */
    public function sendToSubscriber(
        int     $userId,
        int     $tenantId,
        string  $title,
        string  $message,
        ?string $correlationId = null,
    ): void {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        $this->inAppChannel->sendDirect(
            userId:        $userId,
            title:         $title,
            message:       $message,
            type:          'marketplace',
            correlationId: $correlationId,
            tenantId:      $tenantId,
        );
    }

    // ══════════════════════════════════════════════
    //  Private helpers
    // ══════════════════════════════════════════════

    /**
     * Создать пост в канале бизнеса.
     */
    private function createChannelPost(
        BusinessChannel $channel,
        array           $data,
        string          $correlationId,
        int|string      $tenantId,
    ): int|string {
        $postId = $this->db->table('posts')->insertGetId([
            'uuid'           => Str::uuid()->toString(),
            'correlation_id' => $correlationId,
            'channel_id'     => $channel->id,
            'tenant_id'      => $tenantId,
            'title'          => $data['title'] ?? '',
            'content'        => $data['content'] ?? $data['message'] ?? '',
            'slug'           => Str::slug($data['title'] ?? 'notification') . '-' . Str::random(6),
            'status'         => 'published',
            'visibility'     => 'public',
            'published_at'   => now(),
            'is_promo'       => $data['is_promo'] ?? false,
            'is_moderated'   => true,
            'views_count'    => 0,
            'reactions_count' => 0,
            'tags'           => json_encode(['type' => 'notification', 'correlation_id' => $correlationId]),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Обновляем счётчик постов в канале
        $channel->increment('posts_count');
        $channel->update(['last_post_at' => now()]);

        return $postId;
    }

    /**
     * Рассылка in-app уведомлений всем активным подписчикам канала.
     *
     * @return int Количество уведомлённых подписчиков
     */
    private function notifySubscribers(
        BusinessChannel $channel,
        array           $data,
        string          $correlationId,
    ): int {
        $subscribers = ChannelSubscriber::withoutGlobalScopes()
            ->where('channel_id', $channel->id)
            ->whereNull('unsubscribed_at')
            ->pluck('user_id');

        $sent = 0;

        foreach ($subscribers as $userId) {
            try {
                $this->inAppChannel->sendDirect(
                    userId:        (int) $userId,
                    title:         $data['title'] ?? 'Новое в канале',
                    message:       $data['content'] ?? $data['message'] ?? '',
                    type:          'marketplace',
                    correlationId: $correlationId,
                    tenantId:      (int) $channel->tenant_id,
                );
                $sent++;
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to notify subscriber', [
                    'user_id'        => $userId,
                    'channel_id'     => $channel->id,
                    'error'          => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
            }
        }

        return $sent;
    }
}
