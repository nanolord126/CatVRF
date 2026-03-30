<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Medication extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;


        protected $table = 'medications';


        protected $fillable = [


            'tenant_id',


            'uuid',


            'name',


            'inn',


            'sku',


            'price',


            'requires_prescription',


            'stock_quantity',


            'instructions',


            'tags',


            'correlation_id'


        ];


        protected $casts = [


            'requires_prescription' => 'boolean',


            'instructions' => 'json',


            'tags' => 'json',


            'price' => 'integer',


            'stock_quantity' => 'integer'


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', function (Builder $builder) {


                $builder->where('tenant_id', tenant()->id ?? 0);


            });


            static::creating(function (Model $model) {


                $model->uuid = $model->uuid ?? (string) Str::uuid();


                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);


            });


        }
}
