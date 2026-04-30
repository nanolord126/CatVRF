<?php

declare(strict_types=1);

namespace Modules\Marketplace\Infrastructure\Repositories;

use Illuminate\Database\ConnectionInterface;
use Modules\Marketplace\Domain\Entities\AggregationRule;
use Modules\Marketplace\Domain\Interfaces\AggregationRuleRepositoryInterface;
use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Eloquent репозиторий правил агрегации
 */
final class EloquentAggregationRuleRepository implements AggregationRuleRepositoryInterface
{
    private const TABLE = 'marketplace_aggregation_rules';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function save(AggregationRule $rule): void
    {
        $data = $rule->toArray();
        $data['filters'] = json_encode($data['filters']);
        $data['transformations'] = json_encode($data['transformations']);
        $data['category_mapping'] = json_encode($data['category_mapping']);
        $data['attribute_mapping'] = json_encode($data['attribute_mapping']);
        $data['metadata'] = json_encode($data['metadata']);

        $exists = $this->db->table(self::TABLE)
            ->where('uuid', $rule->uuid->toString())
            ->exists();

        if ($exists) {
            $this->db->table(self::TABLE)
                ->where('uuid', $rule->uuid->toString())
                ->update($data);

            $this->logger->debug('Aggregation rule updated', ['uuid' => $rule->uuid->toString()]);
        } else {
            $this->db->table(self::TABLE)->insert($data);

            $this->logger->debug('Aggregation rule created', ['uuid' => $rule->uuid->toString()]);
        }
    }

    public function findByUuid(UuidInterface $uuid): ?AggregationRule
    {
        $record = $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->first();

        if ($record === null) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findActive(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findByVertical(VerticalSource $source): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('source', $source->value)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findPendingSync(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_sync_at')
                    ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_sync_at, NOW()) >= sync_interval_minutes');
            })
            ->orderBy('priority', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function findRealTimeRules(): array
    {
        $records = $this->db->table(self::TABLE)
            ->where('is_active', true)
            ->where('real_time_sync', true)
            ->orderBy('priority', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->all());
    }

    public function delete(UuidInterface $uuid): void
    {
        $this->db->table(self::TABLE)
            ->where('uuid', $uuid->toString())
            ->delete();

        $this->logger->debug('Aggregation rule deleted', ['uuid' => $uuid->toString()]);
    }

    private function mapToEntity(object $record): AggregationRule
    {
        $data = (array) $record;
        $data['filters'] = json_decode($data['filters'], true);
        $data['transformations'] = json_decode($data['transformations'], true);
        $data['category_mapping'] = json_decode($data['category_mapping'], true);
        $data['attribute_mapping'] = json_decode($data['attribute_mapping'], true);
        $data['metadata'] = json_decode($data['metadata'], true);

        return AggregationRule::fromArray($data);
    }
}
