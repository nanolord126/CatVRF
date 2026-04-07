<?php declare(strict_types=1);

namespace App\Models;


use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Позиция заказа (товар / услуга в составе Order).
 * Канон CatVRF 2026.
 *
 * @property int         $id
 * @property int         $order_id
 * @property string|null $product_type
 * @property int|null    $product_id
 * @property string|null $product_name
 * @property int         $quantity
 * @property int         $unit_price     Копейки
 * @property int         $total_price    Копейки
 * @property array|null  $options
 * @property string|null $correlation_id
 */
final class OrderItem extends Model
{
    public function __construct(
        private readonly Request $request,
    ) {}

    protected $table = 'order_items';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'order_id',
        'product_type',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'options',
        'correlation_id',
    ];

    protected $casts = [
        'options'     => 'array',
        'unit_price'  => 'integer',
        'total_price' => 'integer',
        'quantity'    => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
            }
            // Автоматический расчёт total_price
            if ($model->unit_price > 0 && $model->quantity > 0 && $model->total_price === 0) {
                $model->total_price = $model->unit_price * $model->quantity;
            }
        });
    }

    // ─── Отношения ──────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Polymorphic product (BeautyProduct, Dish, FurnitureItem…)
     * Реализуется через отдельный morphTo() в конкретном домене при необходимости.
     */
    public function product()
    {
        return $this->morphTo('product');
    }

    // ─── Вспомогательные методы ─────────────────────────────────────────────

    /** Цена за единицу в рублях */
    public function unitPriceInRubles(): float
    {
        return $this->unit_price / 100;
    }

    /** Итоговая цена в рублях */
    public function totalPriceInRubles(): float
    {
        return $this->total_price / 100;
    }
}
