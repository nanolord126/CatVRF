<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Repositories;

use Modules\Analytics\Domain\Entities\BehavioralEvent;
use Modules\Analytics\Domain\Repositories\BehavioralEventRepositoryInterface;
use Modules\Analytics\Domain\ValueObjects\UserId;
use Modules\Analytics\Domain\ValueObjects\Timestamp;
use Modules\Analytics\Models\BehavioralEvent as BehavioralEventModel;
use Illuminate\Database\ConnectionInterface;
use Carbon\CarbonImmutable;

/**
 * Behavioral Event Repository Implementation
 *
 * Infrastructure layer implementation of the repository interface.
 * Bridges the domain entities with the database models.
 *
 * @package Modules\Analytics\Infrastructure\Repositories
 */
final readonly class BehavioralEventRepository implements BehavioralEventRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function findByUserId(int $userId, int $limit = 100): array
    {
        $models = BehavioralEventModel::where('user_id', $userId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn (BehavioralEventModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByEventType(string $eventType, int $limit = 100): array
    {
        $models = BehavioralEventModel::where('event_type', $eventType)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn (BehavioralEventModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByUserIdAndEventType(int $userId, string $eventType, int $limit = 100): array
    {
        $models = BehavioralEventModel::where('user_id', $userId)
            ->where('event_type', $eventType)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn (BehavioralEventModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function findByDateRange(Timestamp $from, Timestamp $to, ?int $tenantId = null): array
    {
        $query = BehavioralEventModel::whereBetween('occurred_at', [$from->value, $to->value]);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $models = $query->orderBy('occurred_at', 'desc')->get();

        return $models->map(fn (BehavioralEventModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function getUserStatsForRFM(int $tenantId): array
    {
        return BehavioralEventModel::where('tenant_id', $tenantId)
            ->whereIn('event_type', ['order_completed', 'booking_confirmed', 'purchase'])
            ->select(
                'user_id',
                $this->db->raw('MAX(occurred_at) as last_order'),
                $this->db->raw('COUNT(*) as frequency'),
                $this->db->raw('SUM(monetary_value) as total_monetary')
            )
            ->groupBy('user_id')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'last_order' => $row->last_order,
                'frequency' => (int) $row->frequency,
                'total_monetary' => (float) ($row->total_monetary ?? 0),
            ])
            ->keyBy('user_id')
            ->toArray();
    }

    public function findLastPurchase(UserId $userId): ?BehavioralEvent
    {
        $model = BehavioralEventModel::where('user_id', $userId->value)
            ->whereIn('event_type', ['order_completed', 'booking_confirmed', 'purchase'])
            ->where('monetary_value', '>', 0)
            ->orderBy('occurred_at', 'desc')
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function countPurchases(UserId $userId, int $days = 90): int
    {
        return BehavioralEventModel::where('user_id', $userId->value)
            ->whereIn('event_type', ['order_completed', 'booking_confirmed', 'purchase'])
            ->where('occurred_at', '>=', CarbonImmutable::now()->subDays($days))
            ->count();
    }

    public function totalSpend(UserId $userId, int $days = 90): float
    {
        return (float) BehavioralEventModel::where('user_id', $userId->value)
            ->whereIn('event_type', ['order_completed', 'booking_confirmed', 'purchase'])
            ->where('occurred_at', '>=', CarbonImmutable::now()->subDays($days))
            ->sum('monetary_value');
    }

    public function save(BehavioralEvent $event): void
    {
        BehavioralEventModel::create([
            'user_id' => $event->userId,
            'tenant_id' => $event->tenantId,
            'event_type' => $event->eventType,
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
            'metadata' => $event->metadata,
            'monetary_value' => $event->getMonetaryValue(),
            'occurred_at' => $event->occurredAt,
        ]);
    }

    public function saveBatch(array $events): void
    {
        $data = array_map(fn (BehavioralEvent $event) => [
            'user_id' => $event->userId,
            'tenant_id' => $event->tenantId,
            'event_type' => $event->eventType,
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
            'metadata' => $event->metadata,
            'monetary_value' => $event->getMonetaryValue(),
            'occurred_at' => $event->occurredAt,
        ], $events);

        BehavioralEventModel::insert($data);
    }

    public function deleteOlderThan(\DateTimeImmutable $beforeDate): int
    {
        return BehavioralEventModel::where('occurred_at', '<', $beforeDate)
            ->delete();
    }

    public function countByEventType(int $tenantId): array
    {
        return BehavioralEventModel::where('tenant_id', $tenantId)
            ->select('event_type', $this->db->raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->get()
            ->pluck('count', 'event_type')
            ->toArray();
    }

    public function getEventTrends(Timestamp $from, Timestamp $to, string $groupBy = 'day', ?int $tenantId = null): array
    {
        $dateFormat = match ($groupBy) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $query = BehavioralEventModel::whereBetween('occurred_at', [$from->value, $to->value])
            ->select($this->db->raw(sprintf('DATE_FORMAT(occurred_at, \'%s\') as period', $dateFormat)))
            ->selectRaw('COUNT(*) as count')
            ->groupBy('period')
            ->orderBy('period');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()->map(fn ($row) => [
            'period' => $row->period,
            'count' => (int) $row->count,
        ])->toArray();
    }

    public function findByEntity(string $entityType, int $entityId, int $limit = 100): array
    {
        $models = BehavioralEventModel::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn (BehavioralEventModel $model) => $this->toDomainEntity($model))->toArray();
    }

    public function getTopEventTypes(int $tenantId, int $limit = 10): array
    {
        return BehavioralEventModel::where('tenant_id', $tenantId)
            ->select('event_type', $this->db->raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'event_type' => $row->event_type,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    private function toDomainEntity(BehavioralEventModel $model): BehavioralEvent
    {
        return new BehavioralEvent(
            id: $model->id,
            userId: $model->user_id,
            tenantId: $model->tenant_id,
            eventType: $model->event_type,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            metadata: $model->metadata ?? [],
            occurredAt: CarbonImmutable::parse($model->occurred_at),
        );
    }
}
