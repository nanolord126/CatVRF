<?php declare(strict_types=1);

namespace App\Domains\Food\Beverages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'beverage_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'shop_id',
            'customer_id',
            'status',
            'total_amount',
            'payment_status',
            'payment_method',
            'items_snapshot',
            'delivery_type',
            'address',
            'idempotency_key',
            'correlation_id',
            'metadata',
        ];

        protected $casts = [
            'items_snapshot' => 'json',
            'metadata' => 'json',
            'total_amount' => 'integer',
            'customer_id' => 'integer',
            'idempotency_key' => 'string',
            'status' => 'string',
            'payment_status' => 'string',
            'delivery_type' => 'string',
        ];

        /**
         * Boot the model.
         */
        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->idempotency_key)) {
                    $model->idempotency_key = (string) Str::random(32);
                }
            });

            // 2026 Canon: Global Scope Tenant
            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant() !== null) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Parent shop.
         */
        public function shop(): BelongsTo
        {
            return $this->belongsTo(BeverageShop::class, 'shop_id');
        }

        /**
         * Customer who placed the order (B2C or B2B).
         */
        public function customer(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'customer_id');
        }

        /**
         * Scope for pending orders.
         */
        public function scopePending(Builder $query): Builder
        {
            return $query->where('status', 'pending');
        }

        /**
         * Payment status check.
         */
        public function isPaid(): bool
        {
            return $this->payment_status === 'paid';
        }

        /**
         * Order total in human readable format ($XX.YY)
         */
        public function getTotalFormattedAttribute(): string
        {
            return number_format($this->total_amount / 100, 2, '.', ',');
        }
}
