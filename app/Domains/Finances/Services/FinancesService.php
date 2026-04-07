<?php declare(strict_types=1);

namespace App\Domains\Finances\Services;

use App\Domains\Finances\Models\FinanceRecord;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Основной сервис домена Finances — CRUD для финансовых записей.
 *
 * Канон:
 * - fraud-check перед каждой мутацией
 * - DB::transaction()
 * - audit log с correlation_id
 * - tenant-scoping через global scope модели
 *
 * @package App\Domains\Finances\Services
 */
final readonly class FinancesService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

    /**
     * Создание финансовой записи с fraud-check и audit.
     */
    public function create(array $data): FinanceRecord
    {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'finances_create',
            amount: (int) ($data['amount'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($data, $correlationId): FinanceRecord {
            $record = FinanceRecord::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('Finances record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->record(
                action: 'finances_record_created',
                subjectType: FinanceRecord::class,
                subjectId: $record->id,
                newValues: $record->toArray(),
                correlationId: $correlationId,
            );

            return $record;
        });
    }

    /**
     * Обновление финансовой записи с fraud-check и audit.
     */
    public function update(FinanceRecord $record, array $data): FinanceRecord
    {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'finances_update',
            amount: (int) ($data['amount'] ?? $record->amount ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($record, $data, $correlationId): FinanceRecord {
            $oldValues = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('Finances record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'finances_record_updated',
                subjectType: FinanceRecord::class,
                subjectId: $record->id,
                oldValues: $oldValues,
                newValues: $record->fresh()?->toArray() ?? [],
                correlationId: $correlationId,
            );

            return $record->fresh();
        });
    }

    /**
     * Удаление финансовой записи с fraud-check и audit.
     */
    public function delete(FinanceRecord $record): bool
    {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'finances_delete',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $oldValues = $record->toArray();
            $record->delete();

            $this->logger->info('Finances record deleted', [
                'id'             => $oldValues['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'finances_record_deleted',
                subjectType: FinanceRecord::class,
                subjectId: $oldValues['id'] ?? null,
                oldValues: $oldValues,
                correlationId: $correlationId,
            );

            return true;
        });
    }

    /**
     * Получение списка записей с фильтрацией.
     *
     * Tenant-scoping обеспечивается через global scope модели.
     */
    public function list(array $filters = []): Collection
    {
        return FinanceRecord::query()
            ->when(
                !empty($filters['status']),
                fn ($q) => $q->where('status', $filters['status']),
            )
            ->when(
                !empty($filters['type']),
                fn ($q) => $q->where('type', $filters['type']),
            )
            ->when(
                !empty($filters['business_group_id']),
                fn ($q) => $q->where('business_group_id', $filters['business_group_id']),
            )
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): FinanceRecord
    {
        return FinanceRecord::findOrFail($id);
    }
}