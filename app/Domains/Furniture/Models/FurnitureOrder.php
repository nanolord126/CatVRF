<?php declare(strict_types=1);

namespace App\Domains\Furniture\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureOrder extends Model
{
    use HasFactory;

    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'furniture_orders';
        protected $fillable = [
            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
            'item_id', 'client_id', 'client_address', 'delivery_date',
            'assembly_date', 'total_price', 'status', 'idempotency_key', 'tags',
        ];
        protected $casts = [
            'total_price'   => 'int',
            'delivery_date' => 'datetime',
            'assembly_date' => 'datetime',
            'tags'          => 'json',
        ];

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function item(): BelongsTo
        {
            return $this->belongsTo(FurnitureItem::class, 'item_id');
        }

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function isPending(): bool
        {
            return $this->status === 'pending';
        }

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function isDelivered(): bool
        {
            return $this->status === 'delivered';
        }

        /**
         * Выполнить операцию
         *
         * @return mixed
         * @throws \RuntimeException
         */
        public function isAssembled(): bool
        {
            return $this->status === 'assembled';
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()->id) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }
}
