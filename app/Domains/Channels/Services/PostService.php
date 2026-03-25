<?php declare(strict_types=1);

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Models\BusinessChannel;
use App\Domains\Channels\Models\Post;
use App\Domains\Channels\Models\PostMedia;
use App\Services\FraudControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Управление постами бизнес-каналов.
 *
 * Все посты проходят модерацию перед публикацией.
 * Лимиты публикаций проверяются через ChannelTariffService.
 */
final class PostService
{
    public function __construct(
        private readonly ChannelTariffService $tariffService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Создать пост (статус: draft или pending_moderation).
     *
     * @param array<UploadedFile> $mediaFiles
     * @throws \RuntimeException
     */
    public function createPost(
        BusinessChannel $channel,
        string $content,
        string $title = '',
        string $visibility = 'all',
        bool $isPromo = false,
        array $mediaFiles = [],
        ?Carbon $scheduledAt = null,
        ?array $poll = null,
        string $correlationId = '',
    ): Post {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        // Проверка лимита постов
        $this->tariffService->assertPostsLimitNotExceeded($channel);

        // Проверка лимита медиафайлов
        $this->tariffService->assertMediaLimitNotExceeded($channel, count($mediaFiles));

        // Если промо — проверить тариф
        if ($isPromo) {
            $this->tariffService->assertFeatureEnabled($channel, 'promo_enabled');
        }

        // Если опрос — проверить тариф
        if ($poll !== null) {
            $this->tariffService->assertFeatureEnabled($channel, 'polls_enabled');
        }

        // Если отложенная публикация — проверить тариф
        if ($scheduledAt !== null) {
            $this->tariffService->assertFeatureEnabled($channel, 'scheduled_posts');
        }

        // Fraud check
        $fraud = $this->fraudControlService->check(
            userId:        (int) auth()->id(),
            operationType: 'post_create',
            amount:        0,
            correlationId: $correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \RuntimeException('Публикация заблокирована системой безопасности.');
        }

        // Проверить длину контента
        $maxLen = config('channels.limits.max_post_length', 10000);
        if (mb_strlen($content) > $maxLen) {
            throw new \InvalidArgumentException(
                "Текст поста слишком длинный (максимум {$maxLen} символов)."
            );
        }

        return $this->db->transaction(function () use (
            $channel, $content, $title, $visibility, $isPromo,
            $mediaFiles, $scheduledAt, $poll, $correlationId
        ): Post {
            $needsModeration = config('channels.moderation.enabled', true);

            $status = match (true) {
                $scheduledAt !== null => 'draft',      // отложенная — черновик до часа
                $needsModeration      => 'pending_moderation',
                default               => 'published',
            };

            $post = Post::create([
                'uuid'           => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'channel_id'     => $channel->id,
                'tenant_id'      => $channel->tenant_id,
                'title'          => $title ?: null,
                'content'        => $content,
                'slug'           => $this->generateSlug($title ?: $content),
                'status'         => $status,
                'visibility'     => $visibility,
                'scheduled_at'   => $scheduledAt,
                'poll'           => $poll,
                'is_promo'       => $isPromo,
                'reactions'      => [],
                'tags'           => ['channel_slug' => $channel->slug],
            ]);

            // Загрузить медиафайлы
            foreach ($mediaFiles as $idx => $file) {
                $this->attachMedia($post, $file, $idx);
            }

            // Обновить счётчик постов канала
            $channel->increment('posts_count');
            $channel->update(['last_post_at' => now()]);

            // Инвалидировать кэш ленты
            $this->cache->forget("channel_feed:{$channel->id}");
            $this->cache->forget("channel_feed_b2c:{$channel->id}");
            $this->cache->forget("channel_feed_b2b:{$channel->id}");

            $this->log->channel('audit')->info('Post created', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
                'post_id'        => $post->id,
                'status'         => $status,
                'visibility'     => $visibility,
                'media_count'    => count($mediaFiles),
            ]);

            return $post;
        });
    }

    /**
     * Опубликовать пост (модератор / автоматически после модерации).
     */
    public function publishPost(
        Post $post,
        ?string $moderatedBy = null,
        string $correlationId = '',
    ): Post {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($post, $moderatedBy, $correlationId): void {
            $post->update([
                'status'             => 'published',
                'published_at'       => now(),
                'is_moderated'       => true,
                'moderated_by'       => $moderatedBy,
                'moderated_at'       => now(),
            ]);

            $this->cache->forget("post:{$post->id}");
            $this->cache->forget("channel_feed:{$post->channel_id}");

            $this->log->channel('audit')->info('Post published', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $post->tenant_id,
                'post_id'        => $post->id,
                'channel_id'     => $post->channel_id,
                'moderated_by'   => $moderatedBy,
            ]);
        });

        // Уведомить подписчиков
        event(new \App\Domains\Channels\Events\PostPublished($post, $correlationId));

        return $post->refresh();
    }

    /**
     * Отклонить пост модератором.
     */
    public function rejectPost(
        Post $post,
        string $reason,
        string $moderatedBy,
        string $correlationId = '',
    ): Post {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($post, $reason, $moderatedBy, $correlationId): void {
            $post->update([
                'status'             => 'rejected',
                'is_moderated'       => true,
                'moderated_by'       => $moderatedBy,
                'moderated_at'       => now(),
                'moderation_comment' => $reason,
            ]);

            $this->log->channel('audit')->warning('Post rejected by moderator', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $post->tenant_id,
                'post_id'        => $post->id,
                'reason'         => $reason,
                'moderated_by'   => $moderatedBy,
            ]);
        });

        return $post->refresh();
    }

    /**
     * Архивировать пост.
     */
    public function archivePost(Post $post, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($post, $correlationId): void {
            $post->update(['status' => 'archived']);
            $this->cache->forget("post:{$post->id}");

            $this->log->channel('audit')->info('Post archived', [
                'correlation_id' => $correlationId,
                'post_id'        => $post->id,
                'tenant_id'      => $post->tenant_id,
            ]);
        });
    }

    /**
     * Получить ленту постов канала с кэшированием.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFeed(
        BusinessChannel $channel,
        string $audience = 'all',
        int $perPage = 10,
        int $page = 1,
    ) {
        // Лента не кэшируется постранично, только первая страница
        if ($page === 1) {
            $cacheKey = "channel_feed_{$audience}:{$channel->id}";
            return $this->cache->remember(
                $cacheKey,
                config('channels.cache.feed_ttl', 120),
                fn () => $this->buildFeedQuery($channel, $audience)->paginate($perPage)
            );
        }

        return $this->buildFeedQuery($channel, $audience)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Увеличить счётчик просмотров поста.
     */
    public function incrementViews(Post $post, string $userHash = ''): void
    {
        // Используем Redis-инкремент без DB-транзакции для производительности
        $key = "post_views:{$post->id}:{$userHash}";

        // Дедупликация — не считать повторные просмотры с того же юзера за 5 мин
        if (!$this->cache->has($key)) {
            $this->cache->put($key, 1, 300);
            $this->db->table('posts')->where('id', $post->id)->increment('views_count');

            // Запись в daily-статистику
            $this->db->table('post_stats_daily')->updateOrInsert(
                ['post_id' => $post->id, 'stat_date' => today()],
                [
                    'tenant_id' => $post->tenant_id,
                    'views'     => $this->db->raw('views + 1'),
                    'updated_at' => now(),
                ]
            );
        }
    }

    // ──────────────────────────────────────────────────────
    // Private
    // ──────────────────────────────────────────────────────

    private function buildFeedQuery(BusinessChannel $channel, string $audience)
    {
        return Post::withoutGlobalScopes()
            ->where('channel_id', $channel->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($audience !== 'all', fn ($q) => $q->whereIn('visibility', [$audience, 'all']))
            ->with(['media', 'channel:id,name,slug,avatar_url'])
            ->orderByDesc('published_at');
    }

    private function generateSlug(string $text): string
    {
        $base = Str::slug(mb_substr($text, 0, 80));
        $slug = $base ?: Str::uuid()->toString();
        $i    = 1;

        while (Post::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function attachMedia(Post $post, UploadedFile $file, int $sortOrder): PostMedia
    {
        $maxSize = config('channels.limits.max_media_size_bytes', 52_428_800);

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                'Файл слишком большой. Максимальный размер: ' . round($maxSize / 1024 / 1024, 0) . ' MB'
            );
        }

        $mime      = $file->getMimeType() ?? '';
        $type      = match (true) {
            str_starts_with($mime, 'image/')         => 'image',
            str_starts_with($mime, 'video/')         => 'video',
            in_array($mime, ['video/mp4', 'video/quicktime']) => 'shorts',
            default                                  => 'image',
        };

        // Для shorts (короткое вертикальное видео) устанавливаем тип по имени
        if (str_contains($file->getClientOriginalName(), 'short')) {
            $type = 'shorts';
        }

        $path = $this->storage->disk('public')->putFile(
            "channels/{$post->channel_id}/posts/{$post->id}",
            $file
        );

        return PostMedia::create([
            'post_id'        => $post->id,
            'tenant_id'      => $post->tenant_id,
            'type'           => $type,
            'url'            => $this->storage->disk('public')->url($path),
            'mime_type'      => $mime,
            'size_bytes'     => $file->getSize(),
            'sort_order'     => $sortOrder,
            'correlation_id' => Str::uuid()->toString(),
        ]);
    }
}
