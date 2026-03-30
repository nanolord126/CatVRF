<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerOrderItem extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'flower_order_items';

        public $timestamps = false;

        protected $fillable = [
            'order_id',
            'product_id',
            'quantity',
            'unit_price',
            'total_price',
            'customizations',
        ];

        protected $casts = [
            'customizations' => 'json',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];

        public function order(): BelongsTo
        {
            return $this->belongsTo(FlowerOrder::class);
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FlowerProduct::class);
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }
}
