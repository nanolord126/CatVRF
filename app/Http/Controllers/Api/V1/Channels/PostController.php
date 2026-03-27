<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Channels;
use App\Domains\Content\Channels\Models\BusinessChannel;
use App\Domains\Content\Channels\Models\Post;
use App\Domains\Content\Channels\Services\PostService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
/**
 * API управления постами бизнес-канала.
 *
 * GET    /api/v1/channels/{slug}/posts              — лента постов (публично)
 * POST   /api/v1/channels/{uuid}/posts              — создать пост (бизнес)
 * GET    /api/v1/channels/{slug}/posts/{postUuid}   — просмотр поста (публично)
 * PUT    /api/v1/posts/{uuid}                       — редактировать пост (бизнес)
 * DELETE /api/v1/posts/{uuid}                       — архивировать пост (бизнес)
 * POST   /api/v1/posts/{uuid}/publish               — опубликовать (модератор)
 * POST   /api/v1/posts/{uuid}/reject                — отклонить (модератор)
 * GET    /api/v1/feed                               — личная лента (пользователь)
 */
final class PostController extends BaseApiV1Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {}
    /** Лента постов канала (публично) */
    public function index(Request $request, string $slug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $channel  = $this->findPublicChannel($slug);
            $audience = $this->detectAudience($request);
            $page     = (int) $request->query('page', 1);
            $posts = $this->postService->getFeed($channel, $audience, 10, $page);
            return response()->json([
                'success'        => true,
                'data'           => $this->formatPostCollection($posts),
                'meta'           => [
                    'current_page' => $posts->currentPage(),
                    'last_page'    => $posts->lastPage(),
                    'per_page'     => $posts->perPage(),
                    'total'        => $posts->total(),
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 404);
        }
    }
    /** Просмотр одного поста */
    public function show(Request $request, string $slug, string $postUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $post = Post::withoutGlobalScopes()
                ->where('uuid', $postUuid)
                ->where('status', 'published')
                ->whereHas('channel', fn ($q) => $q->where('slug', $slug)->where('status', 'active'))
                ->with(['media', 'channel:id,name,slug,avatar_url'])
                ->firstOrFail();
            // Инкремент просмотров
            $userHash = md5(($request->user()?->id ?? $request->ip()) . date('YmdHi'));
            $this->postService->incrementViews($post, $userHash);
            return response()->json([
                'success'        => true,
                'data'           => $this->formatPost($post),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 404);
        }
    }
    /** Создать пост (только бизнес-тенант) */
    public function store(Request $request, string $channelUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $validated = $request->validate([
            'content'      => ['required', 'string', 'max:10000'],
            'title'        => ['nullable', 'string', 'max:512'],
            'visibility'   => ['in:b2c,b2b,all'],
            'is_promo'     => ['boolean'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'poll'         => ['nullable', 'array'],
            'poll.question' => ['required_with:poll', 'string', 'max:500'],
            'poll.options'  => ['required_with:poll', 'array', 'min:2', 'max:10'],
        ]);
        try {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('uuid', $channelUuid)
                ->firstOrFail();
            $this->authorize('createPost', $channel);
            $scheduledAt = isset($validated['scheduled_at'])
                ? Carbon::parse($validated['scheduled_at'])
                : null;
            $post = $this->postService->createPost(
                channel:       $channel,
                content:       $validated['content'],
                title:         $validated['title'] ?? '',
                visibility:    $validated['visibility'] ?? 'all',
                isPromo:       (bool) ($validated['is_promo'] ?? false),
                mediaFiles:    $request->files->all()['media'] ?? [],
                scheduledAt:   $scheduledAt,
                poll:          $validated['poll'] ?? null,
                correlationId: $correlationId,
            );
            return response()->json([
                'success'        => true,
                'message'        => match ($post->status) {
                    'pending_moderation' => 'Пост отправлен на модерацию.',
                    'draft'              => 'Пост сохранён как черновик (отложенная публикация).',
                    default              => 'Пост опубликован.',
                },
                'data'           => $this->formatPost($post),
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 422);
        }
    }
    /** Опубликовать пост (модератор) */
    public function publish(Request $request, string $postUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $post = Post::withoutGlobalScopes()->where('uuid', $postUuid)->firstOrFail();
            $this->authorize('publish', $post);
            $post = $this->postService->publishPost(
                $post,
                $request->user()?->email,
                $correlationId
            );
            return response()->json([
                'success'        => true,
                'message'        => 'Пост опубликован.',
                'data'           => $this->formatPost($post),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /** Отклонить пост (модератор) */
    public function reject(Request $request, string $postUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        try {
            $post = Post::withoutGlobalScopes()->where('uuid', $postUuid)->firstOrFail();
            $this->authorize('reject', $post);
            $post = $this->postService->rejectPost(
                $post,
                $validated['reason'],
                $request->user()?->email ?? 'system',
                $correlationId
            );
            return response()->json([
                'success'        => true,
                'message'        => 'Пост отклонён.',
                'data'           => $this->formatPost($post),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /** Архивировать пост (бизнес) */
    public function destroy(Request $request, string $postUuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $post = Post::withoutGlobalScopes()->where('uuid', $postUuid)->firstOrFail();
            $this->authorize('delete', $post);
            $this->postService->archivePost($post, $correlationId);
            return response()->json([
                'success'        => true,
                'message'        => 'Пост перенесён в архив.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /** Личная лента пользователя */
    public function feed(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $userId   = $request->user()->id;
            $audience = $this->detectAudience($request);
            $page     = (int) $request->query('page', 1);
            /** @var \App\Domains\Content\Channels\Services\ChannelSubscriptionService $subService */
            $subService = app(\App\Domains\Content\Channels\Services\ChannelSubscriptionService::class);
            $feed       = $subService->getPersonalFeed($userId, $audience, 15);
            return response()->json([
                'success'        => true,
                'data'           => $this->formatPostCollection($feed),
                'meta'           => [
                    'current_page' => $feed->currentPage(),
                    'last_page'    => $feed->lastPage(),
                    'per_page'     => $feed->perPage(),
                    'total'        => $feed->total(),
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    // ──────────────────────────────────────────────────────
    // Private
    // ──────────────────────────────────────────────────────
    private function findPublicChannel(string $slug): BusinessChannel
    {
        return BusinessChannel::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }
    private function detectAudience(Request $request): string
    {
        // B2B пользователи (tenants) видят b2b + all посты
        if ($request->user()?->current_tenant_id) {
            return 'b2b';
        }
        return 'b2c';
    }
    private function formatPost(Post $post): array
    {
        return [
            'id'           => $post->id,
            'uuid'         => $post->uuid,
            'title'        => $post->title,
            'content'      => $post->content,
            'slug'         => $post->slug,
            'status'       => $post->status,
            'visibility'   => $post->visibility,
            'is_promo'     => $post->is_promo,
            'published_at' => $post->published_at?->toIso8601String(),
            'scheduled_at' => $post->scheduled_at?->toIso8601String(),
            'views_count'  => $post->views_count,
            'reactions'    => $post->reactions ?? [],
            'reactions_count' => $post->reactions_count,
            'poll'         => $post->poll,
            'media'        => $post->relationLoaded('media')
                ? $post->media->map(fn ($m) => [
                    'type'             => $m->type,
                    'url'              => $m->url,
                    'thumbnail_url'    => $m->thumbnail_url,
                    'alt_text'         => $m->alt_text,
                    'duration_seconds' => $m->duration_seconds,
                ])->toArray()
                : [],
            'channel' => $post->relationLoaded('channel') ? [
                'name'       => $post->channel?->name,
                'slug'       => $post->channel?->slug,
                'avatar_url' => $post->channel?->avatar_url,
            ] : null,
        ];
    }
    private function formatPostCollection($paginator): array
    {
        return array_map(fn ($p) => $this->formatPost($p), iterator_to_array($paginator));
    }
}
