<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;


use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final class SendPostNotification
{



        public int $tries = 3;

        public function __construct(private readonly NotificationService $notificationService,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger) {}

        public function handle(PostPublished $event): void
        {
            if (!$this->config->get('channels.notifications.push_on_publish', true)) {
                return;
            }

            $post    = $event->post;
            $channel = $post->channel;

            if ($channel === null) {
                return;
            }

            // Отправить только активным подписчикам
            ChannelSubscriber::where('channel_id', $channel->id)
                ->whereNull('unsubscribed_at')
                ->where(function ($q) use ($post): void {
                    $q->where('visibility_preference', 'all')
                      ->orWhere('visibility_preference', $post->visibility);
                })
                ->chunk(200, function ($subscribers) use ($post, $channel, $event): void {
                    foreach ($subscribers as $subscriber) {
                        try {
                            $this->notificationService->send(
                                userId:  $subscriber->user_id,
                                type:    'channel_new_post',
                                message: "Новый пост в канале «{$channel->name}»",
                                data: [
                                    'post_id'        => $post->id,
                                    'post_uuid'      => $post->uuid,
                                    'post_title'     => $post->title,
                                    'channel_slug'   => $channel->slug,
                                    'channel_name'   => $channel->name,
                                    'channel_avatar' => $channel->avatar_url,
                                    'correlation_id' => $event->correlationId,
                                ],
                            );
                        } catch (\Throwable $e) {
                            $this->logger->warning('Failed to send post notification', [
                                'correlation_id' => $event->correlationId,
                                'post_id'        => $post->id,
                                'user_id'        => $subscriber->user_id,
                                'error'          => $e->getMessage(),
                            ]);
                        }
                    }
                });

            $this->logger->info('PostPublished notifications dispatched', [
                'correlation_id' => $event->correlationId,
                'post_id'        => $post->id,
                'channel_id'     => $channel->id,
            ]);
        }
}
