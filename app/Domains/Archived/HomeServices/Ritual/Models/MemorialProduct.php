<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Ritual\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MemorialProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'ritual_memorial_products';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'agency_id',


            'name',


            'description',


            'category',


            'material',


            'price_kopecks',


            'stock_count',


            'customization_options',


            'correlation_id',


            'tags',


        ];


        protected $hidden = [


            'id',


            'created_at',


            'updated_at',


        ];


        protected $casts = [


            'customization_options' => 'json',


            'tags' => 'json',


            'price_kopecks' => 'integer',


            'stock_count' => 'integer',


            'tenant_id' => 'integer',


        ];


        /**


         * Booted method for global scoping and UUID generation.


         */


        protected static function booted(): void


        {


            // Изоляция данных на уровне базы (Tenant Scoping)


            static::addGlobalScope('tenant', function (Builder $builder) {


                if (function_exists('tenant') && tenant('id')) {


                    $builder->where('tenant_id', tenant('id'));


                }


            });


            // Автогенерация UUID и Correlation ID


            static::creating(function (MemorialProduct $model) {


                if (empty($model->uuid)) {


                    $model->uuid = (string) Str::uuid();


                }


                if (empty($model->correlation_id)) {


                    $model->correlation_id = (string) Str::uuid();


                }


                if (empty($model->tenant_id) && function_exists('tenant')) {


                    $model->tenant_id = (int) tenant('id');


                }


            });


        }


        /**


         * Агентство, владеющее товаром.


         */


        public function agency(): BelongsTo


        {


            return $this->belongsTo(RitualAgency::class, 'agency_id');


        }


        /**


         * Область доступных товаров (в наличии).


         */


        public function scopeInStock(Builder $query): Builder


        {


            return $query->where('stock_count', '>', 0);


        }
}
