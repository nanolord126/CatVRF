<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use App\Domains\Supermarket\Enums\ReturnCondition;
use App\Domains\Supermarket\Enums\ReturnReason;
use App\Domains\Supermarket\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Return - Модель возврата товара в Supermarket.
 *
 * Система возвратов учитывает специфику продуктов питания:
 * - Скоропортящиеся товары (мясо, молочка, готовка) - возврат только при браке, в течение 24 часов
 * - Остальные товары - 7 дней
 * - Холодная цепь - возврат только с фото + чек температуры
 * - Поддержка фото/видео доказательств
 * - Автоматический приоритет для холодной цепи
 *
 * @property int $id
 * @property int $order_id ID заказа
 * @property int $buyer_id ID покупателя
 * @property int|null $seller_id ID продавца
 * @property string $status Статус возврата (pending|approved|rejected|completed|refunded)
 * @property string $reason_type Причина возврата
 * @property string|null $reason_comment Комментарий к причине
 * @property int $total_amount Сумма к возврату (в копейках)
 * @property int $refund_amount Фактическая сумма возврата (в копейках)
 * @property bool $is_cold_chain Была ли холодная цепь
 * @property string $return_method Способ возврата (pickup|courier|self_delivery)
 * @property array|null $images Фото/видео доказательства
 * @property string $priority Приоритет (normal|high|urgent)
 * @property string|null $sub_vertical Под-вертикаль продукта
 * @property string|null $reject_reason Причина отказа
 * @property \Illuminate\Support\Carbon|null $approved_at Дата одобрения
 * @property \Illuminate\Support\Carbon|null $completed_at Дата завершения
 * @property \Illuminate\Support\Carbon|null $refunded_at Дата возврата денег
 * @property string|null $correlation_id Correlation ID для трассировки
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Return extends Model
{
    use SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'order_id',
        'buyer_id',
        'seller_id',
        'status',
        'reason_type',
        'reason_comment',
        'total_amount',
        'refund_amount',
        'is_cold_chain',
        'return_method',
        'images',
        'priority',
        'sub_vertical',
        'reject_reason',
        'approved_at',
        'completed_at',
        'refunded_at',
        'correlation_id',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'refund_amount' => 'integer',
        'is_cold_chain' => 'boolean',
        'images' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Заказ, к которому относится возврат.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SupermarketOrder::class, 'order_id');
    }

    /**
     * Покупатель, оформивший возврат.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    /**
     * Продавец (если применимо).
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    /**
     * Товары в возврате.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }

    /**
     * Проверить, находится ли возврат в ожидании.
     */
    public function isPending(): bool
    {
        return $this->status === ReturnStatus::PENDING->value;
    }

    /**
     * Проверить, одобрен ли возврат.
     */
    public function isApproved(): bool
    {
        return $this->status === ReturnStatus::APPROVED->value;
    }

    /**
     * Проверить, отклонен ли возврат.
     */
    public function isRejected(): bool
    {
        return $this->status === ReturnStatus::REJECTED->value;
    }

    /**
     * Проверить, завершен ли возврат.
     */
    public function isCompleted(): bool
    {
        return $this->status === ReturnStatus::COMPLETED->value;
    }

    /**
     * Проверить, возвращены ли деньги.
     */
    public function isRefunded(): bool
    {
        return $this->status === ReturnStatus::REFUNDED->value;
    }

    /**
     * Проверить, является ли возврат срочным (холодная цепь).
     */
    public function isUrgent(): bool
    {
        return $this->is_cold_chain || $this->priority === 'urgent';
    }

    /**
     * Получить enum причины возврата.
     */
    public function getReasonEnum(): ReturnReason
    {
        return ReturnReason::from($this->reason_type);
    }

    /**
     * Получить enum статуса возврата.
     */
    public function getStatusEnum(): ReturnStatus
    {
        return ReturnStatus::from($this->status);
    }

    /**
     * Получить общее количество товаров в возврате.
     */
    public function getTotalQuantity(): int
    {
        return $this->items->sum('quantity');
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

    /**
     * Обновить статус возврата.
     */
    public function updateStatus(ReturnStatus $status): void
    {
        $this->status = $status->value;
        
        match ($status) {
            ReturnStatus::APPROVED => $this->approved_at = now(),
            ReturnStatus::COMPLETED => $this->completed_at = now(),
            ReturnStatus::REFUNDED => $this->refunded_at = now(),
            default => null,
        };
        
        $this->save();
    }

    /**
     * Scope для фильтрации по статусу.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по покупателю.
     */
    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope для срочных возвратов.
     */
    public function scopeUrgent($query)
    {
        return $query->where(function ($q) {
            $q->where('priority', 'urgent')
              ->orWhere('is_cold_chain', true);
        });
    }

    /**
     * Scope для возвратов в ожидании.
     */
    public function scopePending($query)
    {
        return $query->where('status', ReturnStatus::PENDING->value);
    }

    /**
     * Scope для возвратов по под-вертикали.
     */
    public function scopeForSubVertical($query, string $subVertical)
    {
        return $query->where('sub_vertical', $subVertical);
    }
}
