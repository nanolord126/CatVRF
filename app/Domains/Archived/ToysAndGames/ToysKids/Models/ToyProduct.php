<?php declare(strict_types=1);

namespace App\Domains\Archived\ToysAndGames\ToysKids\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ToyProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'toy_products';


        protected $fillable = [


            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',


            'name', 'description', 'category', 'brand',


            'age_min_years', 'age_max_years', 'gender',


            'price', 'current_stock',


            'has_safety_certificate', 'safety_certificate_num',


            'gift_wrapping_available', 'photo_url', 'status', 'tags',


        ];


        protected $casts = [


            'price'                    => 'int',


            'current_stock'            => 'int',


            'age_min_years'            => 'int',


            'age_max_years'            => 'int',


            'has_safety_certificate'   => 'boolean',


            'gift_wrapping_available'  => 'boolean',


            'tags'                     => 'json',


        ];


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
