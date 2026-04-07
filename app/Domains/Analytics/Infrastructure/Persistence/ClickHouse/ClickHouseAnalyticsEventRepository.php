<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Infrastructure\Persistence\ClickHouse;

use App\Domains\Analytics\Domain\Entities\AnalyticsEvent;
use App\Domains\Analytics\Domain\Interfaces\AnalyticsEventRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class ClickHouseAnalyticsEventRepository implements AnalyticsEventRepositoryInterface
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db) {}

    private const TABLE = 'events';

    public function save(AnalyticsEvent $event): void
    {
        $this->db->connection('clickhouse')->table(self::TABLE)->insert([
            $this->toClickHouseFormat($event)
        ]);
    }

    public function saveBulk(array $events): void
    {
        $data = array_map(fn(AnalyticsEvent $event) => $this->toClickHouseFormat($event), $events);
        $this->db->connection('clickhouse')->table(self::TABLE)->insert($data);
    }

    public function getAggregatedData(int $tenantId, string $metric, \DateTime $from, \DateTime $to, string $groupBy): Collection
    {
        // This is a simplified example. Real queries would be more complex.
        $query = $this->db->connection('clickhouse')->table(self::TABLE)
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from, $to]);

        switch ($metric) {
            case 'page_views':
                $query->selectRaw("{$groupBy} as group, count() as value")
                    ->where('event_type', 'page_view');
                break;
            case 'unique_users':
                $query->selectRaw("{$groupBy} as group, uniq(user_id) as value");
                break;
            default:
                return collect();
        }

        return $query->groupBy('group')->get();
    }

    private function toClickHouseFormat(AnalyticsEvent $event): array
    {
        return [
            'uuid' => $event->uuid,
            'tenant_id' => $event->tenant_id,
            'user_id' => $event->user_id,
            'event_type' => $event->event_type,
            'payload' => json_encode($event->payload),
            'vertical' => $event->vertical,
            'ip_address' => $event->ip_address,
            'device_fingerprint' => $event->device_fingerprint,
            'created_at' => $event->created_at->toDateTimeString(),
            'correlation_id' => $event->correlation_id,
        ];
    }
}
