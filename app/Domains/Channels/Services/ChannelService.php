<?php declare(strict_types=1);

namespace App\Domains\Channels\Services;

use App\Domains\Channels\Models\BusinessChannel;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Управление бизнес-каналами.
 *
 * Создание, обновление, архивация канала.
 * Один тенант = один канал.
 */
final class ChannelService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Создать канал бизнеса.
     *
     * @throws \RuntimeException если канал уже существует
     */
    public function createChannel(
        string $tenantId,
        string $name,
        string $description = '',
        ?string $avatarUrl = null,
        ?string $coverUrl = null,
        string $correlationId = '',
    ): BusinessChannel {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        // Fraud check
        $fraud = $this->fraudControlService->check(
            userId:        (int) auth()->id(),
            operationType: 'channel_create',
            amount:        0,
            correlationId: $correlationId,
        );

        if ($fraud['decision'] === 'block') {
            throw new \RuntimeException('Создание канала заблокировано системой безопасности.');
        }

        return $this->db->transaction(function () use ($tenantId, $name, $description, $avatarUrl, $coverUrl, $correlationId): BusinessChannel {

            // Проверка лимита: 1 канал на тенант
            $existing = BusinessChannel::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->first();

            if ($existing !== null) {
                throw new \RuntimeException(
                    'У бизнеса уже есть канал. Каждый бизнес может иметь только один канал.'
                );
            }

            $slug = $this->generateUniqueSlug($name);

            $channel = BusinessChannel::create([
                'uuid'           => Str::uuid()->toString(),
                'tenant_id'      => $tenantId,
                'name'           => $name,
                'slug'           => $slug,
                'description'    => $description,
                'avatar_url'     => $avatarUrl,
                'cover_url'      => $coverUrl,
                'status'         => 'active',
                'correlation_id' => $correlationId,
                'tags'           => ['created_at_event' => now()->toIso8601String()],
            ]);

            $this->log->channel('audit')->info('BusinessChannel created', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $tenantId,
                'channel_id'     => $channel->id,
                'name'           => $name,
            ]);

            return $channel;
        });
    }

    /**
     * Обновить канал.
     */
    public function updateChannel(
        BusinessChannel $channel,
        array $attributes,
        string $correlationId = '',
    ): BusinessChannel {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $allowed = ['name', 'description', 'avatar_url', 'cover_url'];
        $data    = array_intersect_key($attributes, array_flip($allowed));

        if (isset($data['name'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $channel->id);
        }

        $this->db->transaction(function () use ($channel, $data, $correlationId): void {
            $channel->update($data);

            $this->cache->forget("channel:{$channel->id}");
            $this->cache->forget("channel_slug:{$channel->slug}");

            $this->log->channel('audit')->info('BusinessChannel updated', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
                'fields'         => array_keys($data),
            ]);
        });

        return $channel->refresh();
    }

    /**
     * Архивировать канал (вручную или автоматически).
     */
    public function archiveChannel(
        BusinessChannel $channel,
        string $reason = 'manual',
        string $correlationId = '',
    ): void {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        if ($channel->isArchived()) {
            return;
        }

        $this->db->transaction(function () use ($channel, $reason, $correlationId): void {
            $channel->update([
                'status'      => 'archived',
                'archived_at' => now(),
            ]);

            $this->cache->forget("channel:{$channel->id}");

            $this->log->channel('audit')->info('BusinessChannel archived', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
                'reason'         => $reason,
            ]);
        });

        // Уведомить владельца
        event(new \App\Domains\Channels\Events\ChannelArchived($channel, $reason, $correlationId));
    }

    /**
     * Восстановить канал из архива.
     */
    public function restoreChannel(BusinessChannel $channel, string $correlationId = ''): void
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->db->transaction(function () use ($channel, $correlationId): void {
            $channel->update([
                'status'      => 'active',
                'archived_at' => null,
            ]);

            $this->log->channel('audit')->info('BusinessChannel restored from archive', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $channel->tenant_id,
                'channel_id'     => $channel->id,
            ]);
        });
    }

    /**
     * Получить канал тенанта (с кэшем).
     */
    public function getChannelForTenant(string $tenantId): ?BusinessChannel
    {
        return $this->cache->remember(
            "channel_tenant:{$tenantId}",
            config('channels.cache.post_ttl', 300),
            fn () => BusinessChannel::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->with(['plan'])
                ->first()
        );
    }

    /**
     * Обновить счётчик подписчиков.
     */
    public function refreshSubscribersCount(BusinessChannel $channel): void
    {
        $count = $channel->subscribers()->count();
        $channel->update(['subscribers_count' => $count]);
        $this->cache->put("channel_subs:{$channel->id}", $count, config('channels.cache.subs_ttl', 300));
    }

    /**
     * Сгенерировать уникальный slug.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name, '-');
        $slug = $base;
        $i    = 1;

        while (true) {
            $exists = BusinessChannel::withoutGlobalScopes()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists();

            if (!$exists) {
                break;
            }

            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
