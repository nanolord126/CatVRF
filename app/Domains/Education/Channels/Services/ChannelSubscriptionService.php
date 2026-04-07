<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Services;


use Illuminate\Cache\RateLimiter;
use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final readonly class ChannelSubscriptionService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly ChannelService $channelService,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger,
        private readonly RateLimiter $rateLimiter,) {}

        /**
         * Подписаться на канал.
         *
         * @throws \RuntimeException
         */
        public function subscribe(
            int $userId,
            BusinessChannel $channel,
            string $visibilityPreference = 'all',
            string $correlationId = '',
        ): ChannelSubscriber {
            $correlationId = $correlationId ?: Str::uuid()->toString();

            // Rate limit: не более 20 подписок/мин
            $rateLimitKey = "channel_sub_user:{$userId}";
            if ($this->rateLimiter->tooManyAttempts($rateLimitKey, 20)) {
                throw new \RuntimeException('Слишком быстро. Подождите немного.');
            }
            $this->rateLimiter->hit($rateLimitKey, 60);

            if ($channel->isArchived()) {
                throw new \RuntimeException('Канал архивирован. Подписка недоступна.');
            }

            return $this->db->transaction(function () use ($userId, $channel, $visibilityPreference, $correlationId): ChannelSubscriber {
                $subscriber = ChannelSubscriber::where('channel_id', $channel->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($subscriber !== null) {
                    // Переподписка (если отписан)
                    if ($subscriber->unsubscribed_at !== null) {
                        $subscriber->update([
                            'unsubscribed_at'       => null,
                            'subscribed_at'         => Carbon::now(),
                            'visibility_preference' => $visibilityPreference,
                            'correlation_id'        => $correlationId,
                        ]);

                        $this->db->table('business_channels')
                            ->where('id', $channel->id)
                            ->increment('subscribers_count');
                    }

                    return $subscriber->refresh();
                }

                $sub = ChannelSubscriber::create([
                    'channel_id'            => $channel->id,
                    'user_id'               => $userId,
                    'visibility_preference' => $visibilityPreference,
                    'correlation_id'        => $correlationId,
                    'subscribed_at'         => Carbon::now(),
                ]);

                $this->db->table('business_channels')
                    ->where('id', $channel->id)
                    ->increment('subscribers_count');

                cache()->forget("channel_subs:{$channel->id}");

                $this->logger->info('User subscribed to channel', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $userId,
                    'channel_id'     => $channel->id,
                    'tenant_id'      => $channel->tenant_id,
                ]);

                event(new \App\Domains\Content\Channels\Events\ChannelSubscribed($channel, $userId, $correlationId));

                return $sub;
            });
        }

        /**
         * Отписаться от канала.
         */
        public function unsubscribe(
            int $userId,
            BusinessChannel $channel,
            string $correlationId = '',
        ): void {
            $correlationId = $correlationId ?: Str::uuid()->toString();

            $this->db->transaction(function () use ($userId, $channel, $correlationId): void {
                $updated = ChannelSubscriber::where('channel_id', $channel->id)
                    ->where('user_id', $userId)
                    ->whereNull('unsubscribed_at')
                    ->update(['unsubscribed_at' => Carbon::now()]);

                if ($updated > 0) {
                    $this->db->table('business_channels')
                        ->where('id', $channel->id)
                        ->decrement('subscribers_count', 1, ['subscribers_count' => $this->db->raw('GREATEST(subscribers_count - 1, 0)')]);

                    cache()->forget("channel_subs:{$channel->id}");

                    $this->logger->info('User unsubscribed from channel', [
                        'correlation_id' => $correlationId,
                        'user_id'        => $userId,
                        'channel_id'     => $channel->id,
                        'tenant_id'      => $channel->tenant_id,
                    ]);
                }
            });
        }

        /**
         * Проверить, подписан ли пользователь на канал.
         */
        public function isSubscribed(int $userId, int $channelId): bool
        {
            return cache()->remember(
                "is_subscribed:{$userId}:{$channelId}",
                60,
                fn () => ChannelSubscriber::where('channel_id', $channelId)
                    ->where('user_id', $userId)
                    ->whereNull('unsubscribed_at')
                    ->exists()
            );
        }

        /**
         * Получить каналы на которые подписан пользователь.
         */
        public function getSubscribedChannels(int $userId): \Illuminate\Database\Eloquent\Collection
        {
            return BusinessChannel::withoutGlobalScopes()
                ->whereHas('subscribers', fn ($q) => $q
                    ->where('user_id', $userId)
                    ->whereNull('unsubscribed_at')
                )
                ->where('status', 'active')
                ->with(['plan'])
                ->get();
        }

        /**
         * Получить счётчик подписчиков (только для владельца бизнеса).
         */
        public function getSubscribersCount(BusinessChannel $channel): int
        {
            return cache()->remember(
                "channel_subs:{$channel->id}",
                $this->config->get('channels.cache.subs_ttl', 300),
                fn () => ChannelSubscriber::where('channel_id', $channel->id)
                    ->whereNull('unsubscribed_at')
                    ->count()
            );
        }

        /**
         * Персональная лента пользователя из всех подписок.
         *
         * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
         */
        public function getPersonalFeed(int $userId, string $audience = 'all', int $perPage = 15)
        {
            $subscribedChannelIds = ChannelSubscriber::where('user_id', $userId)
                ->whereNull('unsubscribed_at')
                ->pluck('channel_id');

            if ($subscribedChannelIds->isEmpty()) {
                return \App\Domains\Content\Channels\Models\Post::withoutGlobalScopes()->whereRaw('1=0')->paginate($perPage);
            }

            return \App\Domains\Content\Channels\Models\Post::withoutGlobalScopes()
                ->whereIn('channel_id', $subscribedChannelIds)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', Carbon::now())
                ->when($audience !== 'all', fn ($q) => $q->whereIn('visibility', [$audience, 'all']))
                ->with(['media', 'channel:id,name,slug,avatar_url'])
                ->orderByDesc('published_at')
                ->paginate($perPage);
        }
}
