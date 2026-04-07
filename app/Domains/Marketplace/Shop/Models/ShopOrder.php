<?php declare(strict_types=1);

namespace App\Domains\Marketplace\Shop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShopOrder extends Model
{
    use HasFactory;

    use HasFactory, BelongsToTenant;

        protected $table = 'shop_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'total_amount_kopeks',
            'status',
            'payment_status',
            'shipping_address',
            'correlation_id',
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


        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });
        }

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }
}
