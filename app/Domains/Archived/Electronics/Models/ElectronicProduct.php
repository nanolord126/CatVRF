<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'electronic_products';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'correlation_id',
            'name',
            'description',
            'brand',
            'model',
            'price',
            'stock',
            'tags',
            'meta',
        ];

        protected $casts = [
            'tags' => 'json',
            'meta' => 'json',
            'price' => 'integer',
            'stock' => 'integer',
        ];
    }


    use Illuminate\Database\Eloquent\Concerns\HasUuids;


    use Illuminate\Database\Eloquent\Factories\HasFactory;


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\Relations\HasMany;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class ElectronicProduct extends Model


    {


        use HasFactory, HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'electronic_products';


        protected $fillable = [


            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',


            'name', 'description', 'category', 'brand', 'sku',


            'price', 'current_stock', 'warranty_months',


            'specifications', 'photo_url', 'status', 'tags',


        ];


        protected $casts = [


            'price'           => 'int',


            'current_stock'   => 'int',


            'warranty_months' => 'int',


            'specifications'  => 'json',


            'tags'            => 'json',


        ];


        /**


         * Выполнить операцию


         *


         * @return mixed


         * @throws \Exception


         */


        public function warrantyClaims(): HasMany


        {


            return $this->hasMany(WarrantyClaim::class, 'product_id');


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
