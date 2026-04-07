<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailReturn extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'fashion_retail_returns';

        protected $fillable = [
            'uuid',
            'order_id',
            'product_id',
            'reason',
            'status',
            'refund_amount',
            'images',
            'notes',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'images' => 'json',
            'tags' => 'json',
            'refund_amount' => 'decimal:2',
        ];

        public function order(): BelongsTo
        {
            return $this->belongsTo(FashionRetailOrder::class, 'order_id');
        }

        public function product(): BelongsTo
        {
            return $this->belongsTo(FashionRetailProduct::class, 'product_id');
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
