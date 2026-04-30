<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Repositories;

use Modules\Analytics\Domain\Entities\BehavioralEvent;
use Modules\Analytics\Domain\Repositories\BehavioralEventRepositoryInterface;
use Modules\Analytics\Models\BehavioralEvent as BehavioralEventModel;
use Illuminate\Database\ConnectionInterface;
use Carbon\CarbonImmutable;

/**
 * Behavioral Event Repository Implementation
 *
 * Infrastructure layer implementation of the repository interface.
 * Bridges the domain entities with the database models.
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

    public function getUserStatsForRFM(int $tenantId): array
    {
        return BehavioralEventModel::where('tenant_id', $tenantId)
            ->whereIn('event_type', ['order_completed', 'booking_confirmed'])
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

    public function deleteOlderThan(\DateTimeImmutable $beforeDate): int
    {
        return BehavioralEventModel::where('occurred_at', '<', $beforeDate)
            ->delete();
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
