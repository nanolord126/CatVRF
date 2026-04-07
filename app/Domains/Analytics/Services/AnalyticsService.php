<?php declare(strict_types=1);

namespace App\Domains\Analytics\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Domains\Analytics\Models\AnalyticsEvent;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
final readonly class AnalyticsService
{
    public function __construct(private FraudControlService $fraud,
        private AuditService        $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard,
    ) {}

    /**
     * Создание записи с fraud-check, $this->db->transaction и correlation_id.
     */
    public function create(array $data): AnalyticsEvent
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'analytics_create', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($data, $correlationId): AnalyticsEvent {
            $record = AnalyticsEvent::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id'      => tenant()->id ?? $data['tenant_id'] ?? null,
            ]));

            $this->logger->info('Analytics record created', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
                'tenant_id'      => $record->tenant_id,
            ]);

            $this->audit->log('created', AnalyticsEvent::class, $record->id, [], $record->toArray(), $correlationId);

            return $record;
        });
    }

    /**
     * Обновление с fraud-check, $this->db->transaction и correlation_id.
     */
    public function update(AnalyticsEvent $record, array $data): AnalyticsEvent
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'analytics_update', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $data, $correlationId): AnalyticsEvent {
            $old = $record->toArray();
            $record->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->info('Analytics record updated', [
                'id'             => $record->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('updated', AnalyticsEvent::class, $record->id, $old, $record->fresh()->toArray(), $correlationId);

            return $record->fresh();
        });
    }

    /**
     * Удаление с fraud-check и audit.
     */
    public function delete(AnalyticsEvent $record): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'analytics_delete', amount: 0, correlationId: $correlationId ?? '');

        return $this->db->transaction(function () use ($record, $correlationId): bool {
            $old = $record->toArray();
            $record->delete();

            $this->logger->info('Analytics record deleted', [
                'id'             => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log('deleted', AnalyticsEvent::class, $old['id'] ?? null, $old, [], $correlationId);

            return true;
        });
    }

    /**
     * Получение списка с tenant-scoping.
     */
    public function list(array $filters = []): Collection
    {
        return AnalyticsEvent::when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Получение записи по ID.
     */
    public function getById(int $id): AnalyticsEvent
    {
        return AnalyticsEvent::findOrFail($id);
    }
}