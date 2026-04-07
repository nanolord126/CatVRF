<?php declare(strict_types=1);

namespace App\Domains\DemandForecast\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\DemandForecast\Models\DemandForecast;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
final readonly class DemandForecastCoordinatorService
{
    public function __construct(private FraudControlService $fraud,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Создание записи с fraud-check, $this->db->transaction и correlation_id.
     */
    public function create(array $data): DemandForecast
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'demandforecast_create', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $correlationId): DemandForecast {
            $record = DemandForecast::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('DemandForecast record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->log('created', DemandForecast::class, $record->id, [], $record->toArray(), $correlationId);

            return $record;
        });
    }

    /**
     * Обновление с fraud-check, $this->db->transaction и correlation_id.
     */
    public function update(DemandForecast $record, array $data): DemandForecast
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'demandforecast_update', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $data, $correlationId): DemandForecast {
            $old = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('DemandForecast record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('updated', DemandForecast::class, $record->id, $old, $record->fresh()->toArray(), $correlationId);

            return $record->fresh();
        });
    }

    /**
     * Удаление с fraud-check и audit.
     */
    public function delete(DemandForecast $record): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'demandforecast_delete', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $old = $record->toArray();
            $record->delete();

            $this->logger->info('DemandForecast record deleted', [
                'id'             => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('deleted', DemandForecast::class, $old['id'] ?? null, $old, [], $correlationId);

            return true;
        });
    }

    /**
     * Получение списка с tenant-scoping.
     */
    public function list(array $filters = []): Collection
    {
        return DemandForecast::when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): DemandForecast
    {
        return DemandForecast::findOrFail($id);
    }
}