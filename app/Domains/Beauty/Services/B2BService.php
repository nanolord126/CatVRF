<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;


use Illuminate\Contracts\Auth\Guard;
use App\Domains\Beauty\Models\B2BBeautyOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * B2BService — управление B2B-заказами на услуги красоты.
 *
 * Оптовые заказы, специальные условия, расчёт комиссий для юрлиц.
 */
final readonly class B2BService
{
    public function __construct(
        private FraudControlService $fraud,
        private WalletService $walletService,
        private LoggerInterface $auditLogger,
        private \Illuminate\Database\DatabaseManager $db,
        private Guard $guard,
    ) {
    }

    /**
     * Создать B2B-заказ.
     *
     * @param array<string, mixed> $data
     * @throws \RuntimeException При блокировке фрода.
     */
    public function createB2BOrder(
        array  $data,
        int    $tenantId,
        string $correlationId = '',
    ): B2BBeautyOrder {
        $correlationId = $correlationId !== '' ? $correlationId : Uuid::uuid4()->toString();

        $fraudCheck = $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'b2b_beauty_order', amount: 0, correlationId: $correlationId ?? '');

        if (($fraudCheck['decision'] ?? '') === 'block') {
            throw new \RuntimeException('Операция заблокирована службой безопасности.', 403);
        }

        return $this->db->transaction(function () use ($data, $tenantId, $correlationId): B2BBeautyOrder {
            $order = B2BBeautyOrder::create(array_merge($data, [
                'uuid'           => Uuid::uuid4()->toString(),
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]));

            $this->auditLogger->info('B2B Beauty order created.', [
                'order_id'       => $order->id,
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $order;
        });
    }

    /**
     * Возвращает список B2B-заказов для тенанта.
     *
     * @return Collection<int, B2BBeautyOrder>
     */
    public function getTenantOrders(int $tenantId): Collection
    {
        return B2BBeautyOrder::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }
}

