<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\DTOs;

use App\Domains\Supermarket\Enums\ReturnCondition;
use App\Domains\Supermarket\Enums\ReturnReason;

/**
 * CreateReturnData - DTO для создания возврата.
 *
 * Immutable readonly класс для передачи данных возврата между слоями.
 * Содержит информацию о заказе, товарах, причинах и доказательствах.
 *
 * @property int $orderId ID заказа
 * @property int $buyerId ID покупателя
 * @property int|null $sellerId ID продавца
 * @property ReturnReason $reasonType Причина возврата
 * @property string|null $comment Комментарий к причине
 * @property array<int, array{order_item_id: int, product_id: int, quantity: int, price: int, condition: string, comment?: string}> $items Товары в возврате
 * @property bool $isColdChain Была ли холодная цепь
 * @property string $returnMethod Способ возврата (pickup|courier|self_delivery)
 * @property array<int, string>|null $images Фото/видео доказательства
 * @property string|null $subVertical Под-вертикаль продукта
 * @property string|null $correlationId Correlation ID для трассировки
 */
final readonly class CreateReturnData
{
    /**
     * @param int $orderId ID заказа
     * @param int $buyerId ID покупателя
     * @param int|null $sellerId ID продавца
     * @param ReturnReason $reasonType Причина возврата
     * @param string|null $comment Комментарий к причине
     * @param array<int, array{order_item_id: int, product_id: int, quantity: int, price: int, condition: string, comment?: string}> $items Товары в возврате
     * @param bool $isColdChain Была ли холодная цепь
     * @param string $returnMethod Способ возврата
     * @param array<int, string>|null $images Фото/видео доказательства
     * @param string|null $subVertical Под-вертикаль продукта
     * @param string|null $correlationId Correlation ID для трассировки
     */
    public function __construct(
        public readonly int $orderId,
        public readonly int $buyerId,
        public readonly ?int $sellerId,
        public readonly ReturnReason $reasonType,
        public readonly ?string $comment,
        public readonly array $items,
        public readonly bool $isColdChain = false,
        public readonly string $returnMethod = 'pickup',
        public readonly ?array $images = null,
        public readonly ?string $subVertical = null,
        public readonly ?string $correlationId = null,
    ) {
        // Валидация товаров
        foreach ($items as $item) {
            if (!isset($item['order_item_id']) || !isset($item['product_id']) || 
                !isset($item['quantity']) || !isset($item['price']) || !isset($item['condition'])) {
                throw new \InvalidArgumentException('Each item must have order_item_id, product_id, quantity, price, and condition');
            }
            
            if ($item['quantity'] <= 0) {
                throw new \InvalidArgumentException('Item quantity must be greater than 0');
            }
            
            if ($item['price'] < 0) {
                throw new \InvalidArgumentException('Item price cannot be negative');
            }
        }

        // Валидация способа возврата
        if (!in_array($returnMethod, ['pickup', 'courier', 'self_delivery'])) {
            throw new \InvalidArgumentException('Invalid return method');
        }
    }

    /**
     * Создать DTO из массива данных.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            buyerId: $data['buyer_id'],
            sellerId: $data['seller_id'] ?? null,
            reasonType: ReturnReason::from($data['reason_type']),
            comment: $data['comment'] ?? null,
            items: $data['items'],
            isColdChain: $data['is_cold_chain'] ?? false,
            returnMethod: $data['return_method'] ?? 'pickup',
            images: $data['images'] ?? null,
            subVertical: $data['sub_vertical'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }

    /**
     * Преобразовать в массив.
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'reason_type' => $this->reasonType->value,
            'comment' => $this->comment,
            'items' => $this->items,
            'is_cold_chain' => $this->isColdChain,
            'return_method' => $this->returnMethod,
            'images' => $this->images,
            'sub_vertical' => $this->subVertical,
            'correlation_id' => $this->correlationId,
        ];
    }

    /**
     * Получить общую сумму возврата.
     */
    public function getTotalAmount(): int
    {
        return array_sum(array_map(
            fn ($item) => $item['price'] * $item['quantity'],
            $this->items
        ));
    }

    /**
     * Получить общее количество товаров.
     */
    public function getTotalQuantity(): int
    {
        return array_sum(array_map(fn ($item) => $item['quantity'], $this->items));
    }

    /**
     * Проверить, есть ли испорченные товары.
     */
    public function hasSpoiledItems(): bool
    {
        return !empty(array_filter(
            $this->items,
            fn ($item) => $item['condition'] === ReturnCondition::SPOILED->value
        ));
    }

    /**
     * Проверить, есть ли поврежденные товары.
     */
    public function hasDamagedItems(): bool
    {
        return !empty(array_filter(
            $this->items,
            fn ($item) => $item['condition'] === ReturnCondition::DAMAGED->value
        ));
    }

    /**
     * Проверить, есть ли фото доказательства.
     */
    public function hasImages(): bool
    {
        return !empty($this->images) && is_array($this->images);
    }

    /**
     * Получить количество фото доказательств.
     */
    public function getImagesCount(): int
    {
        return $this->hasImages() ? count($this->images) : 0;
    }
}
