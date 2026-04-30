<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\DTOs;

/**
 * SupermarketOrderData - DTO для заказа в Supermarket.
 *
 * Immutable readonly класс для передачи данных заказа между слоями.
 * Содержит информацию о товарах, адресах доставки, пользователе и под-вертикали.
 */
final readonly class SupermarketOrderData
{
    /**
     * @param  int  $userId ID пользователя
     * @param  string|null  $tenantId ID тенанта
     * @param  int|null  $businessGroupId ID бизнес-группы (для B2B)
     * @param  array<int, array{product_id: int, product_name: string, quantity: int, price: int, warehouse_id: int, sub_vertical: string}>  $items Товары в заказе
     * @param  string  $sellerAddress Адрес продавца/склада
     * @param  string  $buyerAddress Адрес покупателя
     * @param  string  $deliverySlot Слот доставки
     * @param  string|null  $subVertical Под-вертикаль (grocery_and_delivery, food, etc.)
     * @param  int|null  $warehouseId ID склада
     * @param  string|null  $correlationId Correlation ID для трассировки
     * @param  bool  $isB2B Флаг B2B заказа
     */
    public function __construct(
        public readonly int $userId,
        public readonly ?string $tenantId,
        public readonly ?int $businessGroupId,
        public readonly array $items,
        public readonly string $sellerAddress,
        public readonly string $buyerAddress,
        public readonly string $deliverySlot,
        public readonly ?string $subVertical,
        public readonly ?int $warehouseId,
        public readonly ?string $correlationId = null,
        public readonly bool $isB2B = false,
    ) {}

    /**
     * Создать DTO из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            tenantId: $data['tenant_id'] ?? (function_exists('tenant') && tenant() ? tenant()->id : null),
            businessGroupId: $data['business_group_id'] ?? null,
            items: $data['items'] ?? [],
            sellerAddress: $data['seller_address'],
            buyerAddress: $data['buyer_address'],
            deliverySlot: $data['delivery_slot'],
            subVertical: $data['sub_vertical'] ?? null,
            warehouseId: $data['warehouse_id'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            isB2B: isset($data['business_group_id']),
        );
    }

    /**
     * Преобразовать в массив.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'items' => $this->items,
            'seller_address' => $this->sellerAddress,
            'buyer_address' => $this->buyerAddress,
            'delivery_slot' => $this->deliverySlot,
            'sub_vertical' => $this->subVertical,
            'warehouse_id' => $this->warehouseId,
            'correlation_id' => $this->correlationId,
            'is_b2b' => $this->isB2B,
        ];
    }

    /**
     * Получить общую сумму заказа.
     */
    public function getTotalAmount(): int
    {
        return array_sum(array_map(fn ($item) => $item['price'] * $item['quantity'], $this->items));
    }
}
