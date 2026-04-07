<?php declare(strict_types=1);

namespace App\Domains\Content\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Content\Models\ContentItem;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
final readonly class ContentService
{
    public function __construct(private FraudControlService $fraud,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
     * Создание записи с fraud-check, $this->db->transaction и correlation_id.
     */
    public function create(array $data): ContentItem
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'content_create', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $correlationId): ContentItem {
            $record = ContentItem::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('Content record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->log('created', ContentItem::class, $record->id, [], $record->toArray(), $correlationId);

            return $record;
        });
    }

    /**
     * Обновление с fraud-check, $this->db->transaction и correlation_id.
     */
    public function update(ContentItem $record, array $data): ContentItem
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'content_update', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $data, $correlationId): ContentItem {
            $old = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('Content record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('updated', ContentItem::class, $record->id, $old, $record->fresh()->toArray(), $correlationId);

            return $record->fresh();
        });
    }

    /**
     * Удаление с fraud-check и audit.
     */
    public function delete(ContentItem $record): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'content_delete', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $old = $record->toArray();
            $record->delete();

            $this->logger->info('Content record deleted', [
                'id'             => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('deleted', ContentItem::class, $old['id'] ?? null, $old, [], $correlationId);

            return true;
        });
    }

    /**
     * Получение списка с tenant-scoping.
     */
    public function list(array $filters = []): Collection
    {
        return ContentItem::when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): ContentItem
    {
        return ContentItem::findOrFail($id);
    }
}