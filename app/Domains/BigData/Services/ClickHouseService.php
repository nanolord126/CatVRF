<?php declare(strict_types=1);

namespace App\Domains\BigData\Services;

use App\Domains\BigData\Models\DataAggregation;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;
use Carbon\CarbonInterface;
use App\Services\AuditService;

final readonly class ClickHouseService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly CarbonInterface $carbon,
        private readonly AuditService $audit,
    ) {}

    /**
     * Aggregate data to ClickHouse
     */
    public function aggregate(string $source, string $type, string $key, float $value, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($source, $type, $key, $value, $correlationId) {
            DataAggregation::create([
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                'source' => $source,
                'aggregation_type' => $type,
                'aggregation_key' => $key,
                'value' => $value,
                'timestamp' => $this->carbon->now(),
            ]);

            $this->audit->record(
                action: 'big_data_aggregation',
                subjectType: DataAggregation::class,
                subjectId: null,
                newValues: ['source' => $source, 'type' => $type],
                correlationId: $correlationId,
            );

            $this->logger->info('Big data aggregation recorded', [
                'source' => $source,
                'type' => $type,
                'key' => $key,
                'value' => $value,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Query aggregated data
     */
    public function query(string $type, string $key, \DateTime $from, \DateTime $to): array
    {
        return DataAggregation::byType($type)
            ->where('aggregation_key', $key)
            ->whereBetween('timestamp', [$from, $to])
            ->orderBy('timestamp')
            ->get()
            ->toArray();
    }

    /**
     * Batch aggregate
     */
    public function batchAggregate(array $records, string $correlationId = ''): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($records, $correlationId) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;
            $batchData = [];
            $now = $this->carbon->now();

            foreach ($records as $record) {
                $batchData[] = [
                    'tenant_id' => $tenantId,
                    'source' => $record['source'],
                    'aggregation_type' => $record['type'],
                    'aggregation_key' => $record['key'],
                    'value' => $record['value'],
                    'timestamp' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($batchData)) {
                DataAggregation::insert($batchData);
            }

            $this->audit->record(
                action: 'big_data_batch_aggregation',
                subjectType: DataAggregation::class,
                subjectId: null,
                newValues: ['count' => count($records)],
                correlationId: $correlationId,
            );

            $this->logger->info('Big data batch aggregation completed', [
                'records_count' => count($records),
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
