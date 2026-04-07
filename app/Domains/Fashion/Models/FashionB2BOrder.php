<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionB2BOrder extends Model
{
    use HasFactory;

    protected $table = 'fashion_b2b_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'fashion_store_id',
            'buyer_inn',
            'total_amount',
            'status',
            'items_json',
            'correlation_id',
            'metadata',
        ];

        protected $casts = [
            'items_json' => 'json',
            'metadata' => 'json',
            'total_amount' => 'integer',
            'tenant_id' => 'integer',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        public function store(): BelongsTo
        {
            return $this->belongsTo(FashionStore::class, 'fashion_store_id');
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
