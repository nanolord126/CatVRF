<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Channels;

use App\Domains\Channels\Models\BusinessChannel;
use App\Domains\Channels\Services\ChannelService;
use App\Domains\Channels\Services\ChannelTariffService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API управления каналами бизнеса.
 *
 * GET    /api/v1/channels                 — получить свой канал (бизнес)
 * POST   /api/v1/channels                 — создать канал
 * PUT    /api/v1/channels/{uuid}          — обновить канал
 * GET    /api/v1/channels/{slug}/public   — публичный просмотр канала
 * POST   /api/v1/channels/{uuid}/subscribe/{planSlug} — оформить тариф
 */
final class ChannelController extends BaseApiV1Controller
{
    public function __construct(
        private readonly ChannelService $channelService,
        private readonly ChannelTariffService $tariffService,
    ) {}

    /** Получить канал текущего тенанта */
    public function show(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $tenantId = $request->user()->current_tenant_id ?? $request->user()->id;
            $channel  = $this->channelService->getChannelForTenant((string) $tenantId);

            if ($channel === null) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Канал не найден. Создайте канал для вашего бизнеса.',
                    'correlation_id' => $correlationId,
                ], 404);
            }

            return response()->json([
                'success'        => true,
                'data'           => $this->formatChannel($channel, true),
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }

    /** Создать канал */
    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'avatar_url'  => ['nullable', 'url', 'max:512'],
            'cover_url'   => ['nullable', 'url', 'max:512'],
        ]);

        try {
            $tenantId = $request->user()->current_tenant_id ?? (string) $request->user()->id;

            $channel = $this->channelService->createChannel(
                tenantId:      (string) $tenantId,
                name:          $validated['name'],
                description:   $validated['description'] ?? '',
                avatarUrl:     $validated['avatar_url'] ?? null,
                coverUrl:      $validated['cover_url'] ?? null,
                correlationId: $correlationId,
            );

            return response()->json([
                'success'        => true,
                'message'        => 'Канал успешно создан.',
                'data'           => $this->formatChannel($channel, true),
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 422);
        }
    }

    /** Обновить канал */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:2000'],
            'avatar_url'  => ['nullable', 'url', 'max:512'],
            'cover_url'   => ['nullable', 'url', 'max:512'],
        ]);

        try {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $this->authorize('update', $channel);

            $channel = $this->channelService->updateChannel($channel, $validated, $correlationId);

            return response()->json([
                'success'        => true,
                'message'        => 'Канал обновлён.',
                'data'           => $this->formatChannel($channel, true),
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId);
        }
    }

    /** Публичный просмотр канала по slug */
    public function publicShow(Request $request, string $slug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $slug)
                ->where('status', 'active')
                ->with(['plan'])
                ->firstOrFail();

            return response()->json([
                'success'        => true,
                'data'           => $this->formatChannel($channel, false),
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 404);
        }
    }

    /** Оформить тарифный план */
    public function subscribeToPlan(Request $request, string $uuid, string $planSlug): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        try {
            $channel = BusinessChannel::withoutGlobalScopes()
                ->where('uuid', $uuid)
                ->firstOrFail();

            $this->authorize('update', $channel);

            $usage = $this->tariffService->subscribe($channel, $planSlug, $correlationId);

            return response()->json([
                'success' => true,
                'message' => "Тариф «{$usage->plan->name}» успешно активирован.",
                'data'    => [
                    'plan'        => $usage->plan->slug,
                    'plan_name'   => $usage->plan->name,
                    'expires_at'  => $usage->expires_at->toIso8601String(),
                    'paid_rubles' => round($usage->amount_paid_kopecks / 100, 2),
                ],
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            return $this->errorResponse($e, $correlationId, 422);
        }
    }

    /** Список доступных тарифных планов */
    public function plans(): JsonResponse
    {
        $plans = $this->tariffService->getPlans()->map(fn ($p) => [
            'id'              => $p->id,
            'slug'            => $p->slug,
            'name'            => $p->name,
            'price_rubles'    => round($p->price_kopecks / 100, 2),
            'posts_per_day'   => $p->posts_per_day,
            'photos_per_post' => $p->photos_per_post,
            'shorts_enabled'  => $p->shorts_enabled,
            'polls_enabled'   => $p->polls_enabled,
            'promo_enabled'   => $p->promo_enabled,
            'advanced_stats'  => $p->advanced_stats,
            'scheduled_posts' => $p->scheduled_posts,
        ]);

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // ──────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────

    private function formatChannel(\App\Domains\Channels\Models\BusinessChannel $channel, bool $isOwner): array
    {
        $data = [
            'id'          => $channel->id,
            'uuid'        => $channel->uuid,
            'name'        => $channel->name,
            'slug'        => $channel->slug,
            'description' => $channel->description,
            'avatar_url'  => $channel->avatar_url,
            'cover_url'   => $channel->cover_url,
            'status'      => $channel->status,
            'posts_count' => $channel->posts_count,
        ];

        // Число подписчиков видит только владелец
        if ($isOwner) {
            $data['subscribers_count'] = $channel->subscribers_count;
            $data['plan']              = $channel->plan?->slug;
            $data['plan_name']         = $channel->plan?->name;
            $data['plan_expires_at']   = $channel->plan_expires_at?->toIso8601String();
        }

        return $data;
    }
}
