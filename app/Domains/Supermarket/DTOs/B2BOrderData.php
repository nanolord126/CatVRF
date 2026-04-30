<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\DTOs;

/**
 * B2BOrderData - DTO для B2B заказа в Supermarket.
 *
 * Immutable readonly класс для передачи данных B2B заказа между слоями.
 * Содержит информацию о компании, товарах, адресах доставки и юридических документах.
 */
final readonly class B2BOrderData
{
    /**
     * @param  int  $userId ID пользователя
     * @param  int  $companyId ID B2B компании
     * @param  string|null  $tenantId ID тенанта
     * @param  array<int, array{product_id: int, product_name: string, quantity: int, price: int, warehouse_id: int}>  $items Товары в заказе
     * @param  string  $sellerAddress Адрес продавца/склада
     * @param  string  $buyerAddress Адрес покупателя (юр. адрес)
     * @param  string  $deliverySlot Слот доставки
     * @param  string|null  $subVertical Под-вертикаль
     * @param  int|null  $warehouseId ID склада
     * @param  string|null  $correlationId Correlation ID для трассировки
     * @param  bool  $requireInvoice Требуется счёт
     * @param  bool  $requireUPD Требуется УПД
     * @param  bool  $requireContract Требуется договор
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $companyId,
        public readonly ?string $tenantId,
        public readonly array $items,
        public readonly string $sellerAddress,
        public readonly string $buyerAddress,
        public readonly string $deliverySlot,
        public readonly ?string $subVertical,
        public readonly ?int $warehouseId,
        public readonly ?string $correlationId = null,
        public readonly bool $requireInvoice = true,
        public readonly bool $requireUPD = true,
        public readonly bool $requireContract = false,
    ) {}

    /**
     * Создать DTO из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            companyId: $data['company_id'],
            tenantId: $data['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : null),
            items: $data['items'] ?? [],
            sellerAddress: $data['seller_address'],
            buyerAddress: $data['buyer_address'],
            deliverySlot: $data['delivery_slot'],
            subVertical: $data['sub_vertical'] ?? null,
            warehouseId: $data['warehouse_id'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            requireInvoice: $data['require_invoice'] ?? true,
            requireUPD: $data['require_upd'] ?? true,
            requireContract: $data['require_contract'] ?? false,
        );
    }

    /**
     * Преобразовать в массив.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
            'tenant_id' => $this->tenantId,
            'items' => $this->items,
            'seller_address' => $this->sellerAddress,
            'buyer_address' => $this->buyerAddress,
            'delivery_slot' => $this->deliverySlot,
            'sub_vertical' => $this->subVertical,
            'warehouse_id' => $this->warehouseId,
            'correlation_id' => $this->correlationId,
            'require_invoice' => $this->requireInvoice,
            'require_upd' => $this->requireUPD,
            'require_contract' => $this->requireContract,
        ];
    }

    /**
     * Получить общее количество товаров.
     */
    public function getTotalQuantity(): int
    {
        return array_sum(array_map(fn ($item) => $item['quantity'], $this->items));
    }

    /**
     * Получить общую сумму заказа (в копейках).
     */
    public function getTotalAmount(): int
    {
        return array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $this->items));
    }
}
