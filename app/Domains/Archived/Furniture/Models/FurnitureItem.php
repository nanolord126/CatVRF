<?php declare(strict_types=1);

namespace App\Domains\Archived\Furniture\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FurnitureItem extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'furniture_items';

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


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class FurnitureItem extends Model


    {


        use HasFactory, HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'furniture_items';


        protected $fillable = [


            'tenant_id', 'business_group_id', 'uuid', 'correlation_id',


            'name', 'description', 'category', 'material', 'style',


            'price', 'current_stock', 'dimensions', 'weight_kg',


            'assembly_required', 'assembly_price', 'photo_url', 'status', 'tags',


        ];


        protected $casts = [


            'price'             => 'int',


            'current_stock'     => 'int',


            'assembly_price'    => 'int',


            'weight_kg'         => 'float',


            'assembly_required' => 'boolean',


            'tags'              => 'json',


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
