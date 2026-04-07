<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\ConstructionAndRepair\Models\ConstructionProject;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
final readonly class ConstructionAndRepairService
{
    public function __construct(private FraudControlService $fraud,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Создание записи с fraud-check, $this->db->transaction и correlation_id.
     */
    public function create(array $data): ConstructionProject
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'constructionandrepair_create', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $correlationId): ConstructionProject {
            $record = ConstructionProject::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('ConstructionAndRepair record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->log('construction_created', [
                'subject_type' => ConstructionProject::class,
                'subject_id'   => $record->id,
                'new_values'   => $record->toArray(),
            ], $correlationId);

            return $record;
        });
    }

    /**
     * Обновление с fraud-check, $this->db->transaction и correlation_id.
     */
    public function update(ConstructionProject $record, array $data): ConstructionProject
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'constructionandrepair_update', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $data, $correlationId): ConstructionProject {
            $old = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('ConstructionAndRepair record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('construction_updated', [
                'subject_type' => ConstructionProject::class,
                'subject_id'   => $record->id,
                'old_values'   => $old,
                'new_values'   => $record->fresh()->toArray(),
            ], $correlationId);

            return $record->fresh();
        });
    }

    /**
     * Удаление с fraud-check и audit.
     */
    public function delete(ConstructionProject $record): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'constructionandrepair_delete', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $old = $record->toArray();
            $record->delete();

            $this->logger->info('ConstructionAndRepair record deleted', [
                'id'             => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('construction_deleted', [
                'subject_type' => ConstructionProject::class,
                'subject_id'   => $old['id'] ?? null,
                'old_values'   => $old,
            ], $correlationId);

            return true;
        });
    }

    /**
     * Получение списка с tenant-scoping.
     */
    public function list(array $filters = []): Collection
    {
        return ConstructionProject::when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): ConstructionProject
    {
        return ConstructionProject::findOrFail($id);
    }
}