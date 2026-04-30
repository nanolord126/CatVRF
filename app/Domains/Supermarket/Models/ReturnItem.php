<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use App\Domains\Supermarket\Enums\ReturnCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReturnItem - Модель товара в возврате.
 *
 * Хранит детальную информацию о каждом товаре в возврате:
 * - Ссылка на оригинальный товар заказа
 * - Количество и цена
 * - Состояние товара (good|spoiled|damaged|opened)
 * - Сумма к возврату
 *
 * @property int $id
 * @property int $return_id ID возврата
 * @property int|null $order_item_id ID позиции в заказе
 * @property int|null $product_id ID продукта
 * @property int $quantity Количество
 * @property int $price_per_unit Цена за единицу (в копейках)
 * @property int $refund_amount Сумма к возврату (в копейках)
 * @property string $condition Состояние товара
 * @property string|null $comment Комментарий
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_id',
        'order_item_id',
        'product_id',
        'quantity',
        'price_per_unit',
        'refund_amount',
        'condition',
        'comment',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'integer',
        'refund_amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Возврат, к которому относится товар.
     */
    public function return(): BelongsTo
    {
        return $this->belongsTo(Return::class);
    }

    /**
     * Оригинальная позиция в заказе.
     * Note: SupermarketOrderItem table may not exist yet, this is optional.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Supermarket\Models\SupermarketOrderItem::class, 'order_item_id');
    }

    /**
     * Продукт.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Catalog\Models\Product::class);
    }

    /**
     * Получить enum состояния товара.
     */
    public function getConditionEnum(): ReturnCondition
    {
        return ReturnCondition::from($this->condition);
    }

    /**
     * Проверить, испорчен ли товар.
     */
    public function isSpoiled(): bool
    {
        return $this->condition === ReturnCondition::SPOILED->value;
    }

    /**
     * Проверить, поврежден ли товар.
     */
    public function isDamaged(): bool
    {
        return $this->condition === ReturnCondition::DAMAGED->value;
    }

    /**
     * Проверить, в хорошем ли состоянии товар.
     */
    public function isGood(): bool
    {
        return $this->condition === ReturnCondition::GOOD->value;
    }

    /**
     * Проверить, вскрыт ли товар.
     */
    public function isOpened(): bool
    {
        return $this->condition === ReturnCondition::OPENED->value;
    }

    /**
     * Получить полную сумму возврата для этого товара.
     */
    public function getTotalRefundAmount(): int
    {
        return $this->refund_amount;
    }

    /**
     * Scope для фильтрации по состоянию.
     */
    public function scopeWithCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Scope для испорченных товаров.
     */
    public function scopeSpoiled($query)
    {
        return $query->where('condition', ReturnCondition::SPOILED->value);
    }

    /**
     * Scope для поврежденных товаров.
     */
    public function scopeDamaged($query)
    {
        return $query->where('condition', ReturnCondition::DAMAGED->value);
    }
}
