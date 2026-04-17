<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerOrderItem extends Model
{

        protected $table = 'flower_order_items';

        public $timestamps = false;

        protected $fillable = [
        'uuid',
        'correlation_id',
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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
