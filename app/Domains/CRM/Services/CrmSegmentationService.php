<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmSegmentDto;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmSegment;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager;

use App\Services\FraudControlService;
/**
 * CrmSegmentationService — управление сегментами CRM-клиентов.
 * Динамическая и статическая сегментация, пересчёт.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CrmSegmentationService
{
    public function __construct(
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    

        private readonly FraudControlService $fraud
    ) {}

    /**
     * Создать новый сегмент.
     */
    public function createSegment(CreateCrmSegmentDto $dto): CrmSegment
    {
        return $this->db->transaction(function () use ($dto): CrmSegment {
            $segment = CrmSegment::query()->create($dto->toArray());

            if ($dto->isDynamic) {
                $this->recalculateSegment($segment, $dto->correlationId);
            }

            $this->logger->info('CRM segment created', [
                'segment_id' => $segment->id,
                'name' => $dto->name,
                'is_dynamic' => $dto->isDynamic,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_segment_created',
                CrmSegment::class,
                $segment->id,
                [],
                $dto->toArray(),
                $dto->correlationId
    );

            return $segment;
        });
    }

    /**
     * Пересчитать динамический сегмент — применить правила и обновить count.
     */
    public function recalculateSegment(CrmSegment $segment, string $correlationId): int
    {
        $query = CrmClient::query()->forTenant($segment->tenant_id);

        foreach ($segment->rules as $rule) {
            $field = $rule['field'] ?? '';
            $operator = $rule['operator'] ?? '=';
            $value = $rule['value'] ?? null;

            $query = match ($operator) {
                '>' => $query->where($field, '>', $value),
                '<' => $query->where($field, '<', $value),
                '>=' => $query->where($field, '>=', $value),
                '<=' => $query->where($field, '<=', $value),
                '!=' => $query->where($field, '!=', $value),
                'like' => $query->where($field, 'like', "%{$value}%"),
                'in' => $query->whereIn($field, (array) $value),
                'not_in' => $query->whereNotIn($field, (array) $value),
                'is_null' => $query->whereNull($field),
                'not_null' => $query->whereNotNull($field),
                'days_ago_gt' => $query->where($field, '<', now()->subDays((int) $value)),
                'days_ago_lt' => $query->where($field, '>', now()->subDays((int) $value)),
                default => $query->where($field, $value),
            };
        }

        $count = $query->count();

        $segment->update([
            'clients_count' => $count,
            'last_calculated_at' => now(),
        ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $segment->id ?? null,
                'correlation_id' => $correlationId,
            ]);

        // Для динамических — обновляем pivot-таблицу
        if ($segment->is_dynamic) {
            $clientIds = $query->pluck('id');
            $segment->clients()->sync($clientIds);
        }

        $this->logger->info('CRM segment recalculated', [
            'segment_id' => $segment->id,
            'clients_count' => $count,
            'correlation_id' => $correlationId,
        ]);

        return $count;
    }

    /**
     * Пересчитать все динамические сегменты tenant'а.
     */
    public function recalculateAllSegments(int $tenantId, string $correlationId): int
    {
        $segments = CrmSegment::query()
            ->forTenant($tenantId)
            ->active()
            ->where('is_dynamic', true)
            ->get();

        $total = 0;

        foreach ($segments as $segment) {
            $this->recalculateSegment($segment, $correlationId);
            $total++;
        }

        $this->logger->info('All CRM segments recalculated', [
            'tenant_id' => $tenantId,
            'segments_count' => $total,
            'correlation_id' => $correlationId,
        ]);

        return $total;
    }

    /**
     * Автоматически определить сегмент клиента (VIP, Лояльный, Спящий, Новичок, At Risk).
     */
    public function autoSegmentClient(CrmClient $client): string
    {
        $daysSinceLastOrder = $client->last_order_at
            ? now()->diffInDays($client->last_order_at)
            : 999;

        $totalSpent = (float) ($client->total_spent ?? 0);
        $totalOrders = (int) ($client->total_orders ?? 0);
        $daysSinceCreated = now()->diffInDays($client->created_at);

        if ($client->status === 'blacklist') {
            return 'blacklist';
        }

        if ($totalSpent >= 100000 || $client->loyalty_tier === 'vip') {
            return 'vip';
        }

        if ($totalOrders >= 10 && $daysSinceLastOrder <= 30) {
            return 'loyal';
        }

        if ($daysSinceLastOrder > 90) {
            return 'sleeping';
        }

        if ($daysSinceLastOrder > 45 && $daysSinceLastOrder <= 90) {
            return 'at_risk';
        }

        if ($daysSinceCreated <= 30 && $totalOrders <= 1) {
            return 'new';
        }

        return 'regular';
    }

    /**
     * Пересчитать авто-сегменты для всех клиентов tenant'а.
     */
    public function autoSegmentAllClients(int $tenantId, string $correlationId): int
    {
        $clients = CrmClient::query()->forTenant($tenantId)->get();
        $updated = 0;

        foreach ($clients as $client) {
            $newSegment = $this->autoSegmentClient($client);

            if ($client->segment !== $newSegment) {
                $client->update(['segment' => $newSegment]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);
                $updated++;
            }
        }

        $this->logger->info('CRM auto-segmentation complete', [
            'tenant_id' => $tenantId,
            'updated' => $updated,
            'correlation_id' => $correlationId,
        ]);

        return $updated;
    }

    /**
     * Список предустановленных сегментов для Beauty.
     */
    public function getBeautyPresets(): array
    {
        return [
            ['name' => 'VIP-клиенты', 'slug' => 'vip', 'rules' => [['field' => 'loyalty_tier', 'operator' => '=', 'value' => 'vip']]],
            ['name' => 'Лояльные', 'slug' => 'loyal', 'rules' => [['field' => 'total_orders', 'operator' => '>=', 'value' => 10]]],
            ['name' => 'Спящие (60+ дней)', 'slug' => 'sleeping', 'rules' => [['field' => 'last_order_at', 'operator' => 'days_ago_gt', 'value' => 60]]],
            ['name' => 'Новички', 'slug' => 'new', 'rules' => [['field' => 'created_at', 'operator' => 'days_ago_lt', 'value' => 30]]],
            ['name' => 'С аллергией', 'slug' => 'allergy', 'rules' => [['field' => 'vertical_data->allergies', 'operator' => 'not_null', 'value' => null]]],
        ];
    }

    /**
     * Список предустановленных сегментов для Hotels.
     */
    public function getHotelPresets(): array
    {
        return [
            ['name' => 'VIP-гости', 'slug' => 'vip', 'rules' => [['field' => 'status', 'operator' => '=', 'value' => 'vip']]],
            ['name' => 'Постоянные', 'slug' => 'frequent', 'rules' => [['field' => 'total_orders', 'operator' => '>=', 'value' => 5]]],
            ['name' => 'Новые гости', 'slug' => 'new', 'rules' => [['field' => 'total_orders', 'operator' => '<=', 'value' => 1]]],
            ['name' => 'Давно не были (90+ дней)', 'slug' => 'inactive', 'rules' => [['field' => 'last_order_at', 'operator' => 'days_ago_gt', 'value' => 90]]],
            ['name' => 'Blacklist', 'slug' => 'blacklist', 'rules' => [['field' => 'status', 'operator' => '=', 'value' => 'blacklist']]],
        ];
    }

    /**
     * Список предустановленных сегментов для Flowers.
     */
    public function getFlowerPresets(): array
    {
        return [
            ['name' => 'Корпоративные', 'slug' => 'corporate', 'rules' => [['field' => 'client_type', 'operator' => '=', 'value' => 'corporate']]],
            ['name' => 'Постоянные', 'slug' => 'loyal', 'rules' => [['field' => 'total_orders', 'operator' => '>=', 'value' => 5]]],
            ['name' => 'Спящие (45+ дней)', 'slug' => 'sleeping', 'rules' => [['field' => 'last_order_at', 'operator' => 'days_ago_gt', 'value' => 45]]],
            ['name' => 'Оптовики', 'slug' => 'wholesaler', 'rules' => [['field' => 'client_type', 'operator' => '=', 'value' => 'wholesaler']]],
            ['name' => 'Флористы-партнёры', 'slug' => 'partner', 'rules' => [['field' => 'client_type', 'operator' => '=', 'value' => 'partner']]],
        ];
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
