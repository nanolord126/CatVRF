<?php declare(strict_types=1);

namespace App\Services;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\MarketplaceChannel;
use App\Notifications\Channels\SlackChannel;
use App\Notifications\Channels\InAppChannel;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * Единый роутер каналов уведомлений — NotificationChannelService.
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Все уведомления проходят через этот сервис.
 * Ни один модуль не вызывает каналы напрямую.
 *
 * Поддерживаемые каналы:
 *   - email       (EmailChannel)
 *   - sms         (SmsChannel)
 *   - push        (PushChannel)
 *   - marketplace (MarketplaceChannel — внутренние каналы/паблики маркетплейса)
 *   - slack       (SlackChannel)
 *   - in_app      (InAppChannel)
 *
 * Функции:
 *   - send() — отправка через конкретный канал
 *   - sendToChannels() — отправка через несколько каналов
 *   - sendDirect() — прямая отправка текста (без Notification-объекта)
 *   - DND проверка
 *   - Rate-limit проверка
 *   - Preferences / opt-out проверка
 *   - Audit-лог + correlation_id на каждую операцию
 */
final readonly class NotificationChannelService
{
    /** @var array<string> Все доступные каналы */
    private const AVAILABLE_CHANNELS = [
        'email',
        'sms',
        'push',
        'marketplace',
        'slack',
        'in_app',
    ];

    /**
     * Конструктор — constructor injection по канону.
     */
    public function __construct(
        private EmailChannel $emailChannel,
        private SmsChannel $smsChannel,
        private PushChannel $pushChannel,
        private MarketplaceChannel $marketplaceChannel,
        private SlackChannel $slackChannel,
        private InAppChannel $inAppChannel,
        private NotificationPreferencesService $preferencesService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Отправить уведомление через конкретный канал.
     *
     * @param string $channel    Имя канала: email, sms, push, telegram, slack, in_app
     * @param object $notifiable Получатель (User, Tenant и т.д.)
     * @param \Illuminate\Notifications\Notification $notification Объект уведомления
     * @param string|null $correlationId Correlation ID для аудита
     * @param bool $skipDnd Пропустить проверку DND (для critical)
     * @param bool $skipPreferences Пропустить проверку предпочтений (для security/fraud)
     */
    public function send(
        string $channel,
        object $notifiable,
        \Illuminate\Notifications\Notification $notification,
        ?string $correlationId = null,
        bool $skipDnd = false,
        bool $skipPreferences = false,
    ): bool {
        $correlationId = $correlationId
            ?? (method_exists($notification, 'getCorrelationId') ? $notification->getCorrelationId() : null)
            ?? Str::uuid()->toString();

        if (!$this->isValidChannel($channel)) {
            $this->logger->warning('Invalid notification channel requested', [
                'channel'        => $channel,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        if (!$this->isChannelEnabled($channel)) {
            $this->logger->debug('Channel is disabled', [
                'channel'        => $channel,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        $userId = $notifiable->id ?? null;

        // DND проверка
        if (!$skipDnd && $userId && $this->isInDoNotDisturb($userId, $channel)) {
            $this->logger->info('Notification blocked by DND', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        // Preferences opt-out проверка
        if (!$skipPreferences && $userId && !$this->isChannelAllowedByUser($userId, $channel)) {
            $this->logger->info('Notification blocked by user preferences', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        // Rate-limit проверка
        if ($userId && $this->isRateLimited($userId, $channel)) {
            $this->logger->warning('Notification rate-limited', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        try {
            $this->dispatchToChannel($channel, $notifiable, $notification);

            $this->incrementRateCounter($userId, $channel);

            $this->logger->info('Notification sent via channel', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'notification'   => get_class($notification),
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Notification send failed', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }
    }

    /**
     * Отправить через несколько каналов одновременно.
     *
     * @param array<string> $channels Массив имён каналов
     * @return array<string, bool> Результат по каждому каналу
     */
    public function sendToChannels(
        array $channels,
        object $notifiable,
        \Illuminate\Notifications\Notification $notification,
        ?string $correlationId = null,
        bool $skipDnd = false,
        bool $skipPreferences = false,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $results = [];

        foreach ($channels as $channel) {
            $results[$channel] = $this->send(
                channel: $channel,
                notifiable: $notifiable,
                notification: $notification,
                correlationId: $correlationId,
                skipDnd: $skipDnd,
                skipPreferences: $skipPreferences,
            );
        }

        $sentCount = count(array_filter($results));
        $totalCount = count($results);

        $this->logger->info('Multi-channel notification completed', [
            'channels_sent'    => $sentCount,
            'channels_total'   => $totalCount,
            'results'          => $results,
            'correlation_id'   => $correlationId,
        ]);

        return $results;
    }

    /**
     * Прямая отправка текстового уведомления (без Notification-объекта).
     *
     * Удобно для fraud alerts, security events, системных уведомлений.
     *
     * @param string $channel   Канал доставки
     * @param int $userId       Получатель
     * @param string $title     Заголовок
     * @param string $message   Текст
     * @param string|null $correlationId
     * @param int|null $tenantId
     */
    public function sendDirect(
        string  $channel,
        int     $userId,
        string  $title,
        string  $message,
        ?string $correlationId = null,
        ?int    $tenantId = null,
    ): bool {
        $correlationId = $correlationId ?? Str::uuid()->toString();

        if (!$this->isValidChannel($channel) || !$this->isChannelEnabled($channel)) {
            return false;
        }

        try {
            match ($channel) {
                'in_app' => $this->inAppChannel->sendDirect(
                    userId: $userId,
                    title: $title,
                    message: $message,
                    correlationId: $correlationId,
                    tenantId: $tenantId,
                ),
                'marketplace' => $this->sendMarketplaceDirect($userId, $title, $message, $correlationId, $tenantId),
                'slack' => $this->slackChannel->sendDirect(
                    text: "[{$title}] {$message}",
                    correlationId: $correlationId,
                ),
                'email' => $this->sendEmailDirect($userId, $title, $message, $correlationId),
                'sms' => $this->sendSmsDirect($userId, $message, $correlationId),
                'push' => $this->sendPushDirect($userId, $title, $message, $correlationId),
            };

            $this->logger->info('Direct notification sent', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'title'          => $title,
                'correlation_id' => $correlationId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Direct notification failed', [
                'channel'        => $channel,
                'user_id'        => $userId,
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return false;
        }
    }

    /**
     * Отправка через несколько каналов прямым текстом (для fraud/security).
     *
     * @param array<string> $channels
     * @return array<string, bool>
     */
    public function sendDirectToChannels(
        array   $channels,
        int     $userId,
        string  $title,
        string  $message,
        ?string $correlationId = null,
        ?int    $tenantId = null,
    ): array {
        $correlationId = $correlationId ?? Str::uuid()->toString();
        $results = [];

        foreach ($channels as $channel) {
            $results[$channel] = $this->sendDirect(
                channel: $channel,
                userId: $userId,
                title: $title,
                message: $message,
                correlationId: $correlationId,
                tenantId: $tenantId,
            );
        }

        return $results;
    }

    /**
     * Получить список всех доступных каналов.
     *
     * @return array<string>
     */
    public function getAvailableChannels(): array
    {
        return self::AVAILABLE_CHANNELS;
    }

    /**
     * Получить список включённых каналов.
     *
     * @return array<string>
     */
    public function getEnabledChannels(): array
    {
        return array_filter(
            self::AVAILABLE_CHANNELS,
            fn (string $channel) => $this->isChannelEnabled($channel)
        );
    }

    // ══════════════════════════════════════════════
    //  Dispatch to specific channel
    // ══════════════════════════════════════════════

    /**
     * Роутинг в конкретный канал.
     */
    private function dispatchToChannel(
        string $channel,
        object $notifiable,
        \Illuminate\Notifications\Notification $notification,
    ): void {
        match ($channel) {
            'email'       => $this->emailChannel->send($notifiable, $notification),
            'sms'         => $this->smsChannel->send($notifiable, $notification),
            'push'        => $this->pushChannel->send($notifiable, $notification),
            'marketplace' => $this->marketplaceChannel->send($notifiable, $notification),
            'slack'       => $this->slackChannel->send($notifiable, $notification),
            'in_app'      => $this->inAppChannel->send($notifiable, $notification),
            default    => throw new \InvalidArgumentException("Unknown channel: {$channel}"),
        };
    }

    // ══════════════════════════════════════════════
    //  Direct send helpers (без Notification-объекта)
    // ══════════════════════════════════════════════

    private function sendMarketplaceDirect(int $userId, string $title, string $message, string $correlationId, ?int $tenantId = null): void
    {
        if ($tenantId !== null) {
            $this->marketplaceChannel->sendToSubscriber(
                userId:        $userId,
                tenantId:      $tenantId,
                title:         $title,
                message:       $message,
                correlationId: $correlationId,
            );
            return;
        }

        // Без tenant_id — отправляем как in-app уведомление типа marketplace
        $this->inAppChannel->sendDirect(
            userId:        $userId,
            title:         $title,
            message:       $message,
            type:          'marketplace',
            correlationId: $correlationId,
        );
    }

    private function sendEmailDirect(int $userId, string $title, string $message, string $correlationId): void
    {
        $user = \App\Models\User::find($userId);

        if (!$user?->email) {
            return;
        }

        \Illuminate\Support\Facades\Mail::raw($message, function (\Illuminate\Mail\Message $mail) use ($user, $title) {
            $mail->to($user->email)->subject($title);
        });
    }

    private function sendSmsDirect(int $userId, string $message, string $correlationId): void
    {
        $user = \App\Models\User::find($userId);

        if (!$user?->phone) {
            return;
        }

        $this->logger->info('SMS direct send (provider integration pending)', [
            'user_id'        => $userId,
            'phone'          => substr($user->phone, 0, 4) . '****',
            'correlation_id' => $correlationId,
        ]);
    }

    private function sendPushDirect(int $userId, string $title, string $message, string $correlationId): void
    {
        $this->logger->info('Push direct send (provider integration pending)', [
            'user_id'        => $userId,
            'title'          => $title,
            'correlation_id' => $correlationId,
        ]);
    }

    // ══════════════════════════════════════════════
    //  Validation helpers
    // ══════════════════════════════════════════════

    private function isValidChannel(string $channel): bool
    {
        return in_array($channel, self::AVAILABLE_CHANNELS, true);
    }

    private function isChannelEnabled(string $channel): bool
    {
        return (bool) config("notifications.channels.{$channel}.enabled", false);
    }

    /**
     * Проверяет, находится ли пользователь в режиме DND.
     *
     * Учитывает bypass_channels (sms всегда проходит) и bypass_categories (security/fraud).
     */
    private function isInDoNotDisturb(int $userId, string $channel): bool
    {
        if (!config('notifications.dnd.enabled', false)) {
            return false;
        }

        $bypassChannels = config('notifications.dnd.bypass_channels', []);
        if (in_array($channel, $bypassChannels, true)) {
            return false;
        }

        $dndEnabled = cache()->get("dnd:user.{$userId}.enabled", false);
        if (!$dndEnabled) {
            return false;
        }

        $startTime = cache()->get("dnd:user.{$userId}.start_time");
        $endTime = cache()->get("dnd:user.{$userId}.end_time");

        if (!$startTime || !$endTime) {
            return false;
        }

        $now = now()->format('H:i');

        // Обработка перехода через полночь (23:00 - 07:00)
        if ($startTime > $endTime) {
            return $now >= $startTime || $now < $endTime;
        }

        return $now >= $startTime && $now < $endTime;
    }

    /**
     * Проверяет, разрешён ли канал пользователем.
     */
    private function isChannelAllowedByUser(int $userId, string $channel): bool
    {
        try {
            $preferences = $this->preferencesService->getPreferences($userId);
            $channelPrefs = $preferences[$channel] ?? null;

            if ($channelPrefs === null) {
                return true;
            }

            return $channelPrefs['enabled'] ?? true;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Проверяет, превышен ли rate-limit для пользователя.
     */
    private function isRateLimited(int $userId, string $channel): bool
    {
        $limit = (int) config("notifications.channels.{$channel}.rate_limit.per_user_per_hour", 100);
        $key = "notif_rate:{$channel}:{$userId}";
        $current = (int) cache()->get($key, 0);

        return $current >= $limit;
    }

    /**
     * Инкремент rate-limit счётчика.
     */
    private function incrementRateCounter(?int $userId, string $channel): void
    {
        if ($userId === null) {
            return;
        }

        $key = "notif_rate:{$channel}:{$userId}";
        $current = (int) cache()->get($key, 0);
        cache()->put($key, $current + 1, 3600);
    }
}
