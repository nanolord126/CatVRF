<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Services;

use App\Domains\Supermarket\DTOs\B2BOrderData;
use App\Domains\Supermarket\Models\B2BCompany;
use App\Domains\Supermarket\Models\B2BPriceRule;
use App\Domains\Supermarket\Models\Product;
use App\Domains\Supermarket\Models\SupermarketOrder;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * B2B Service - главный фасад для B2B операций в Supermarket.
 *
 * Отвечает за:
 * - Расчёт оптовых цен
 * - Создание B2B заказов
 * - Управление правилами ценообразования
 * - Генерация юридических документов
 */
final readonly class B2BService
{
    use WithAuditLogging;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly FraudControlService $fraudControl,
        private readonly AuditService $audit,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получить оптовую цену для товара.
     *
     * Приоритет правил:
     * 1. Персональное правило продавца (B2BPriceRule) - установленная цена
     * 2. Розничная цена (fallback)
     *
     * @param  Product  $product Товар
     * @param  int  $quantity Количество
     * @param  B2BCompany|null  $company Компания покупателя (опционально)
     * @return int Цена в копейках
     */
    public function getWholesalePrice(Product $product, int $quantity, ?B2BCompany $company = null): int
    {
        $cacheKey = "b2b:price:{$product->id}:{$quantity}:" . ($company?->id ?? 'guest');

        return Cache::tags(['b2b', 'pricing', "product:{$product->id}"])
            ->remember($cacheKey, now()->addMinutes(15), function () use ($product, $quantity) {
                $rule = B2BPriceRule::where('product_id', $product->id)
                    ->where('tenant_id', $product->tenant_id)
                    ->where('min_quantity', '<=', $quantity)
                    ->where('is_active', true)
                    ->orderBy('min_quantity', 'desc')
                    ->first();

                if ($rule && $rule->isValid()) {
                    return $rule->getEffectivePrice($product->price);
                }

                return $product->price;
            });
    }

    /**
     * Создать B2B заказ.
     *
     * @param  B2BOrderData  $data Данные заказа
     * @return array{order_id: int, total_amount: int, documents_required: array}
     * @throws \Exception Если компания не подтверждена или не выполнены условия
     */
    public function createB2BOrder(B2BOrderData $data): array
    {
        $correlationId = $data->correlationId ?? $this->generateCorrelationId();

        $this->logger->info('B2B order creation started', [
            'correlation_id' => $correlationId,
            'user_id' => $data->userId,
            'company_id' => $data->companyId,
        ]);

        // Проверяем статус компании
        $company = B2BCompany::findOrFail($data->companyId);

        if (!$company->isApproved()) {
            throw new \RuntimeException('Company is not approved for B2B purchases');
        }

        // Проверяем минимальную сумму заказа
        $config = config('verticals.verticals.supermarket.b2b');
        $minAmount = $config['min_amount'] ?? 400000; // 4000 RUB
        $totalAmount = $data->getTotalAmount();

        if ($totalAmount < $minAmount) {
            throw new \RuntimeException(
                sprintf('Minimum order amount is %d RUB. Current: %d RUB', $minAmount / 100, $totalAmount / 100)
            );
        }

        return $this->db->transaction(function () use ($data, $company, $correlationId, $totalAmount) {
            // Fraud check
            $this->fraudControl->check([
                'operation_type' => 'b2b_order_create',
                'vertical' => 'supermarket',
                'user_id' => $data->userId,
                'company_id' => $data->companyId,
                'amount' => $totalAmount,
                'correlation_id' => $correlationId,
            ]);

            // Пересчитываем цены с оптовыми скидками
            $itemsWithWholesalePrices = [];
            foreach ($data->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $wholesalePrice = $this->getWholesalePrice($product, $item['quantity'], $company);

                $cashback = $this->getCashbackAmount($product, $item['quantity'], $company);
                $itemsWithWholesalePrices[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $wholesalePrice,
                    'cashback' => $cashback,
                    'warehouse_id' => $item['warehouse_id'],
                ];
            }

            // Создаём заказ
            $order = SupermarketOrder::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $data->tenantId,
                'user_id' => $data->userId,
                'sub_vertical' => $data->subVertical,
                'status' => 'pending',
                'total_amount' => array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $itemsWithWholesalePrices)),
                'delivery_cost' => 0, // B2B доставка рассчитывается отдельно
                'delivery_eta' => null,
                'delivery_address' => json_encode(['address' => $data->buyerAddress]),
                'delivery_slot' => json_encode(['slot' => $data->deliverySlot]),
                'cold_chain_required' => false,
                'correlation_id' => $correlationId,
            ]);

            // Добавляем B2B метаданные
            $this->db->table('supermarket_orders')
                ->where('id', $order->id)
                ->update([
                    'is_b2b' => true,
                    'b2b_company_id' => $data->companyId,
                    'items' => json_encode($itemsWithWholesalePrices),
                ]);

            $documentsRequired = [];
            if ($data->requireInvoice) {
                $documentsRequired[] = 'invoice';
            }
            if ($data->requireUPD) {
                $documentsRequired[] = 'upd';
            }
            if ($data->requireContract) {
                $documentsRequired[] = 'contract';
            }

            // Диспатчим задачу генерации документов
            if (!empty($documentsRequired)) {
                dispatch(new \App\Domains\Supermarket\Jobs\GenerateB2BDocumentsJob(
                    orderId: $order->id,
                    documents: $documentsRequired,
                    correlationId: $correlationId,
                ))->onQueue('b2b-documents');
            }

            $this->logCreated(
                entityType: 'b2b_order',
                entityId: $order->id,
                userId: $data->userId,
                context: [
                    'correlation_id' => $correlationId,
                    'company_id' => $data->companyId,
                    'total_quantity' => $totalQuantity,
                    'total_amount' => $totalAmount,
                    'discount_level' => $company->discount_level,
                    'documents_required' => $documentsRequired,
                ],
            );

            $this->logger->info('B2B order created', [
                'order_id' => $order->id,
                'correlation_id' => $correlationId,
                'company_id' => $data->companyId,
                'total_amount' => $order->total_amount,
            ]);

            return [
                'order_id' => $order->id,
                'uuid' => $order->uuid,
                'total_amount' => $totalAmount,
                'total_cashback' => $totalCashback,
                'documents_required' => $documentsRequired,
                'correlation_id' => $correlationId,
            ];
        });
    }

    /**
     * Получить каталог продуктов с оптовыми ценами.
     *
     * @param  int  $companyId ID компании
     * @param  array  $filters Фильтры (category, sub_vertical, etc.)
     * @param  int  $page Страница
     * @param  int  $perPage Количество на странице
     * @return array{products: array, pagination: array}
     */
    public function getB2BCatalog(int $companyId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $company = B2BCompany::findOrFail($companyId);

        if (!$company->isApproved()) {
            throw new \RuntimeException('Company is not approved for B2B purchases');
        }

        $query = Product::query()
            ->where('tenant_id', $company->user_id) // Только товары этого тенанта
            ->where('is_active', true);

        // Применяем фильтры
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['sub_vertical'])) {
            $query->where('sub_vertical', $filters['sub_vertical']);
        }
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $products = collect($paginator->items())->map(function ($product) use ($company) {
            $wholesalePrice = $this->getWholesalePrice($product, 1, $company);
            $discountPercent = $product->price > 0
                ? round((($product->price - $wholesalePrice) / $product->price) * 100, 1)
                : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'sub_vertical' => $product->sub_vertical,
                'retail_price' => $product->price,
                'wholesale_price' => $wholesalePrice,
                'discount_percent' => $discountPercent,
                'image_url' => $product->image_url,
                'requires_cold_chain' => $product->requires_cold_chain,
            ];
        })->toArray();

        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Создать правило оптовой цены.
     *
     * @param  array  $data Данные правила
     * @return B2BPriceRule
     */
    public function createPriceRule(array $data): B2BPriceRule
    {
        $correlationId = $this->generateCorrelationId();

        $rule = B2BPriceRule::create([
            'product_id' => $data['product_id'],
            'tenant_id' => $data['tenant_id'],
            'min_quantity' => $data['min_quantity'],
            'price_per_unit' => $data['price_per_unit'] ?? 0,
            'cashback_percent' => $data['cashback_percent'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        // Инвалидируем кэш
        Cache::tags(['b2b', 'pricing', "product:{$data['product_id']}"])->flush();

        $this->logCreated(
            entityType: 'b2b_price_rule',
            entityId: $rule->id,
            userId: $data['user_id'] ?? null,
            context: [
                'correlation_id' => $correlationId,
                'product_id' => $data['product_id'],
                'tenant_id' => $data['tenant_id'],
                'min_quantity' => $data['min_quantity'],
            ],
        );

        return $rule;
    }

    /**
     * Получить сумму кэшбэка для товара.
     *
     * @param  Product  $product Товар
     * @param  int  $quantity Количество
     * @param  B2BCompany|null  $company Компания покупателя (опционально)
     * @return int Сумма кэшбэка в копейках
     */
    public function getCashbackAmount(Product $product, int $quantity, ?B2BCompany $company = null): int
    {
        $rule = B2BPriceRule::where('product_id', $product->id)
            ->where('tenant_id', $product->tenant_id)
            ->where('min_quantity', '<=', $quantity)
            ->where('is_active', true)
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($rule && $rule->isValid()) {
            $price = $this->getWholesalePrice($product, $quantity, $company);
            return $rule->getCashbackAmount($price);
        }

        return 0;
    }
}
