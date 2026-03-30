<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BHomeServiceStorefront extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;


        protected $table = 'b2b_home_service_storefronts';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'business_group_id',


            'company_name',


            'inn',


            'description',


            'service_categories',


            'wholesale_discount',


            'min_order_amount',


            'is_verified',


            'is_active',


            'correlation_id',


            'tags',


        ];


        protected $casts = [


            'service_categories' => 'json',


            'tags' => 'json',


            'is_verified' => 'boolean',


            'is_active' => 'boolean',


            'wholesale_discount' => 'decimal:2',


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', function ($query) {


                if (auth()->check() && auth()->user()->tenant_id) {


                    $query->where('tenant_id', auth()->user()->tenant_id);


                }


            });


        }


        public function b2bOrders(): HasMany


        {


            return $this->hasMany(B2BHomeServiceOrder::class, 'b2b_home_service_storefront_id');


        }
}
