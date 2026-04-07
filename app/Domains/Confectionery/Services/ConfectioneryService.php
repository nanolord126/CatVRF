<?php

declare(strict_types=1);

namespace App\Domains\Confectionery\Services;

use App\Domains\Confectionery\DTOs\CreateOrderDto;
use App\Domains\Confectionery\DTOs\CreateProductDto;
use App\Domains\Confectionery\Models\BakeryOrder;
use App\Domains\Confectionery\Models\Cake;
use App\Domains\Confectionery\Models\ConfectioneryShop;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Class ConfectioneryService
 *
 * Главный сервис вертикали Confectionery (Кондитерские изделия).
 * Слой 3: Services — CatVRF 2026, 9-layer architecture.
 *
 * Обеспечивает CRUD-операции для кондитерских магазинов, товаров и заказов.
 * Все мутации проходят через FraudControlService::check() + DB::transaction().
 * Каждое действие логируется с correlation_id.
 *
 * @package App\Domains\Confectionery\Services
 */
final readonly class ConfectioneryService
{
    /**
     * @param FraudControlService $fraud      Сервис проверки на фрод
     * @param AuditService        $audit      Сервис аудит-логирования
     * @param LoggerInterface     $logger     PSR-3 логгер
     */
    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private LoggerInterface $logger,
        private DatabaseManager $db,
    ) {}

    /**
     * Получить список магазинов текущего tenant.
     *
     * @param int      $tenantId      Идентификатор tenant
     * @param int|null $perPage       Количество на страницу (по умолчанию 20)
     * @param string   $correlationId Идентификатор корреляции запроса
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listShops(int $tenantId, ?int $perPage, string $correlationId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->logger->info('Listing confectionery shops', [
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return ConfectioneryShop::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate($perPage ?? 20);
    }

    /**
     * Получить магазин по ID с проверкой принадлежности tenant.
     *
     * @param int    $shopId        Идентификатор магазина
     * @param int    $tenantId      Идентификатор tenant
     * @param string $correlationId Идентификатор корреляции запроса
     *
     * @return ConfectioneryShop
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getShopById(int $shopId, int $tenantId, string $correlationId): ConfectioneryShop
    {
        $this->logger->info('Fetching confectionery shop', [
            'shop_id' => $shopId,
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        return ConfectioneryShop::where('tenant_id', $tenantId)
            ->findOrFail($shopId);
    }

    /**
     * Получить список товаров (Cake) для данного магазина.
     *
     * @param int    $shopId        Идентификатор магазина
     * @param string $correlationId Идентификатор корреляции запроса
     *
     * @return Collection<int, Cake>
     */
    public function listProducts(int $shopId, string $correlationId): Collection
    {
        $this->logger->info('Listing products for shop', [
            'shop_id' => $shopId,
            'correlation_id' => $correlationId,
        ]);

        return Cake::where('confectionery_shop_id', $shopId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Создать заказ в кондитерской.
     *
     * @param CreateOrderDto $dto DTO с данными заказа
     *
     * @return BakeryOrder
     *
     * @throws \App\Exceptions\FraudBlockedException
     */
    public function createOrder(CreateOrderDto $dto): BakeryOrder
    {
        $this->fraud->check(userId: $dto->userId, operationType: 'confectionery_order_create', amount: 0, correlationId: $dto->correlationId);

        return $this->db->transaction(function () use ($dto): BakeryOrder {
            $order = BakeryOrder::create($dto->toArray());

            $this->audit->log(
                'confectionery_order_created',
                [
                    'subject_type' => BakeryOrder::class,
                    'subject_id'   => $order->id,
                    'new_values'   => $order->toArray(),
                ],
                $dto->correlationId,
            );

            $this->logger->info('Bakery order created', [
                'order_id' => $order->id,
                'correlation_id' => $dto->correlationId,
            ]);

            return $order;
        });
    }
}
