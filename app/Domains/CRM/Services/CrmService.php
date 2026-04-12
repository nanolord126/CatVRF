<?php

declare(strict_types=1);

namespace App\Domains\CRM\Services;


use Illuminate\Support\Facades\DB;
use App\Domains\CRM\DTOs\CreateCrmClientDto;
use App\Domains\CRM\DTOs\CreateCrmInteractionDto;
use App\Domains\CRM\Events\CrmClientCreated;
use App\Domains\CRM\Events\CrmInteractionRecorded;
use App\Domains\CRM\Models\CrmClient;
use App\Domains\CRM\Models\CrmInteraction;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Log\LogManager;

/**
 * CrmService — главный сервис CRM.
 * Управление клиентами, взаимодействиями, поиск и фильтрация.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CrmService
{
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private LogManager $logger,
    
    ) {}

    /**
     * Создать нового CRM-клиента.
     */
    public function createClient(CreateCrmClientDto $dto): CrmClient
    {
        $this->fraud->check(
            userId: $dto->userId ?? 0,
            operationType: 'crm_client_create',
            amount: 0,
            correlationId: $dto->correlationId
    );

        return $this->db->transaction(function () use ($dto): CrmClient {
            $client = CrmClient::query()->create($dto->toArray());

            $this->logger->info('CRM client created', [
                'client_id' => $client->id,
                'tenant_id' => $dto->tenantId,
                'vertical' => $dto->vertical,
                'correlation_id' => $dto->correlationId,
            ]);

            $this->audit->log(
                'crm_client_created',
                CrmClient::class,
                $client->id,
                [],
                $dto->toArray(),
                $dto->correlationId
    );

            CrmClientCreated::dispatch($client, $dto->correlationId);

            return $client;
        });
    }

    /**
     * Обновить карточку CRM-клиента.
     */
    public function updateClient(CrmClient $client, array $data, string $correlationId): CrmClient
    {
        $this->fraud->check(
            userId: $client->user_id ?? 0,
            operationType: 'crm_client_update',
            amount: 0,
            correlationId: $correlationId
    );

        return $this->db->transaction(function () use ($client, $data, $correlationId): CrmClient {
            $oldValues = $client->toArray();

            $client->update($data);

            $this->logger->info('CRM client updated', [
                'client_id' => $client->id,
                'tenant_id' => $client->tenant_id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'crm_client_updated',
                CrmClient::class,
                $client->id,
                $oldValues,
                $data,
                $correlationId
    );

            return $client->fresh() ?? $client;
        });
    }

    /**
     * Записать взаимодействие с клиентом.
     */
    public function recordInteraction(CreateCrmInteractionDto $dto): CrmInteraction
    {
        return $this->db->transaction(function () use ($dto): CrmInteraction {
            $interaction = CrmInteraction::query()->create($dto->toArray());

            // Обновляем дату последнего взаимодействия на клиенте
            CrmClient::query()
                ->where('id', $dto->crmClientId)
                ->update(['last_interaction_at' => now()]);

            $this->logger->info('CRM interaction recorded', [
                'interaction_id' => $interaction->id,
                'client_id' => $dto->crmClientId,
                'type' => $dto->type,
                'correlation_id' => $dto->correlationId,
            ]);

            CrmInteractionRecorded::dispatch($interaction, $dto->correlationId);

            return $interaction;
        });
    }

    /**
     * Получить клиента по ID с tenant-scoping.
     */
    public function getClientById(int $clientId, int $tenantId): CrmClient
    {
        return CrmClient::query()
            ->forTenant($tenantId)
            ->with(['beautyProfile', 'hotelGuestProfile', 'flowerClientProfile'])
            ->findOrFail($clientId);
    }

    /**
     * Обновить финансовую сводку клиента (пересчёт из заказов).
     */
    public function recalculateClientStats(CrmClient $client, string $correlationId): void
    {
        $this->db->transaction(function () use ($client, $correlationId): void {
            // Денормализация — пересчитываем из interactions или внешних источников
            $orderInteractions = $client->interactions()
                ->where('type', 'order')
                ->get();

            $totalSpent = 0;
            $totalOrders = 0;

            foreach ($orderInteractions as $interaction) {
                $meta = $interaction->metadata ?? [];
                $totalSpent += $meta['amount'] ?? 0;
                $totalOrders++;
            }

            $averageOrderValue = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;

            $client->update([
                'total_spent' => $totalSpent,
                'total_orders' => $totalOrders,
                'average_order_value' => round($averageOrderValue, 2),
            ]);

            $this->logger->channel('audit')->info(class_basename(static::class) . ': Record updated', [
                'id' => $client->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('CRM client stats recalculated', [
                'client_id' => $client->id,
                'total_spent' => $totalSpent,
                'total_orders' => $totalOrders,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Список клиентов для tenant с фильтрами.
     */
    public function listClients(
        int $tenantId,
        ?string $vertical = null,
        ?string $segment = null,
        ?string $status = null,
        ?string $search = null,
        int $perPage = 20
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = CrmClient::query()->forTenant($tenantId);

        if ($vertical !== null) {
            $query->byVertical($vertical);
        }

        if ($segment !== null) {
            $query->bySegment($segment);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc('last_interaction_at')->paginate($perPage);
    }

    /**
     * Глобальная лента взаимодействий для tenant.
     */
    public function getGlobalFeed(int $tenantId, int $limit = 50): Collection
    {
        return CrmInteraction::query()
            ->where('tenant_id', $tenantId)
            ->with('client')
            ->orderByDesc('interacted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Выполнить операцию внутри транзакции.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
