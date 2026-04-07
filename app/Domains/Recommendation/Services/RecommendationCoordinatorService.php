<?php declare(strict_types=1);

namespace App\Domains\Recommendation\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Recommendation\Models\Recommendation;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
final readonly class RecommendationCoordinatorService
{
    public function __construct(private FraudControlService $fraud,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Создание записи с fraud-check, $this->db->transaction и correlation_id.
     */
    public function create(array $data): Recommendation
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'recommendation_create', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $correlationId): Recommendation {
            $record = Recommendation::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()?->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('Recommendation record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->log('created', Recommendation::class, $record->id, [], $record->toArray(), $correlationId);

            return $record;
        });
    }

    /**
     * Обновление с fraud-check, $this->db->transaction и correlation_id.
     */
    public function update(Recommendation $record, array $data): Recommendation
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'recommendation_update', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $data, $correlationId): Recommendation {
            $old = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('Recommendation record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('updated', Recommendation::class, $record->id, $old, $record->fresh()->toArray(), $correlationId);

            return $record->fresh();
        });
    }

    /**
     * Удаление с fraud-check и audit.
     */
    public function delete(Recommendation $record): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'recommendation_delete', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $old = $record->toArray();
            $record->delete();

            $this->logger->info('Recommendation record deleted', [
                'id'             => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('deleted', Recommendation::class, $old['id'] ?? null, $old, [], $correlationId);

            return true;
        });
    }

    /**
     * Получение списка с tenant-scoping.
     */
    public function list(array $filters = []): Collection
    {
        return Recommendation::when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): Recommendation
    {
        return Recommendation::findOrFail($id);
    }
}