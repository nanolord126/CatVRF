<?php declare(strict_types=1);

namespace App\Domains\Archived\MeatShops\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'meat_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'correlation_id',
            'status',
            'total_price',
            'tags',
            'meta',
        ];

        protected $casts = [
            'tags' => 'json',
            'meta' => 'json',
            'total_price' => 'integer',
        ];
    }


    use Illuminate\Database\Eloquent\Concerns\HasUuids;


    use Illuminate\Database\Eloquent\Factories\HasFactory;


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\Relations\BelongsTo;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class MeatOrder extends Model


    {


        use HasFactory, HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'meat_orders';


        protected $fillable = [


            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',


            'product_id', 'client_id', 'weight_kg', 'unit_price',


            'total_price', 'delivery_date', 'status', 'idempotency_key', 'tags',


        ];


        protected $casts = [


            'weight_kg'    => 'float',


            'unit_price'   => 'int',


            'total_price'  => 'int',


            'delivery_date' => 'datetime',


            'tags'         => 'json',


        ];


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function product(): BelongsTo


        {


            return $this->belongsTo(MeatProduct::class, 'product_id');


        }


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function isPending(): bool


        {


            return $this->status === 'pending';


        }


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function isDelivered(): bool


        {


            return $this->status === 'delivered';


        }


        protected static function booted(): void


        {


            parent::booted();


            static::addGlobalScope('tenant_id', function ($query) {


                if (function_exists('tenant') && tenant('id')) {


                    $query->where('tenant_id', tenant('id'));


                }


            });


        }
}
