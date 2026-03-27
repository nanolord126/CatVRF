<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Channels;
use App\Domains\Content\Channels\Models\BusinessChannel;
use App\Domains\Content\Channels\Services\ChannelSubscriptionService;
use App\Domains\Content\Channels\Services\ChannelService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
/**
 * API подписок пользователей на каналы.
 *
 * POST   /api/v1/channels/{slug}/subscribe   — подписаться
 * DELETE /api/v1/channels/{slug}/subscribe   — отписаться
 * GET    /api/v1/channels/{slug}/subscribe   — статус подписки
 * GET    /api/v1/subscriptions/channels      — мои подписки
 * GET    /api/v1/subscriptions/feed          — личная лента (proxy → PostController::feed)
 */
final class SubscriberController extends BaseApiV1Controller
{
    public function __construct(
        private readonly ChannelSubscriptionService $subscriptionService,
    ) {}
    /** Подписаться на канал */
    public function subscribe(Request $request, string $slug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        $validated = $request->validate([
            'visibility_preference' => ['in:b2c,b2b,all'],
        ]);
        try {
            $channel = $this->findActiveChannel($slug);
            $this->subscriptionService->subscribe(
                userId:                (int) $request->user()->id,
                channel:               $channel,
                visibilityPreference:  $validated['visibility_preference'] ?? 'all',
                correlationId:         $correlationId,
            );
            return response()->json([
                'success'        => true,
                'message'        => "Вы подписались на канал «{$channel->name}».",
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 422);
        }
    }
    /** Отписаться от канала */
    public function unsubscribe(Request $request, string $slug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $channel = $this->findActiveChannel($slug);
            $this->subscriptionService->unsubscribe(
                userId:        (int) $request->user()->id,
                channel:       $channel,
                correlationId: $correlationId,
            );
            return response()->json([
                'success'        => true,
                'message'        => "Вы отписались от канала «{$channel->name}».",
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    /** Проверить статус подписки */
    public function status(Request $request, string $slug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $channel     = $this->findActiveChannel($slug);
            $isSubscribed = $this->subscriptionService->isSubscribed(
                (int) $request->user()->id,
                $channel->id
            );
            return response()->json([
                'success'        => true,
                'subscribed'     => $isSubscribed,
                'channel_name'   => $channel->name,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 404);
        }
    }
    /** Мои подписки */
    public function mySubscriptions(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        try {
            $channels = $this->subscriptionService->getSubscribedChannels((int) $request->user()->id);
            return response()->json([
                'success' => true,
                'data'    => $channels->map(fn ($c) => [
                    'id'          => $c->id,
                    'uuid'        => $c->uuid,
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'avatar_url'  => $c->avatar_url,
                    'posts_count' => $c->posts_count,
                    'plan'        => $c->plan?->slug,
                ]),
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }
    private function findActiveChannel(string $slug): BusinessChannel
    {
        return BusinessChannel::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
