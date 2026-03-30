<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Photographer extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;


        protected $table = 'photography_photographers';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'user_id',


            'full_name',


            'specialization',


            'experience_years',


            'base_price_hour_kopecks',


            'equipment_json',


            'is_available',


            'correlation_id'


        ];


        protected $casts = [


            'uuid' => 'string',


            'equipment_json' => 'json',


            'is_available' => 'boolean',


            'base_price_hour_kopecks' => 'integer',


            'experience_years' => 'integer',


        ];


        protected static function booted(): void


        {


            static::creating(function (self $model) {


                $model->uuid ??= (string) Str::uuid();


                $model->tenant_id ??= tenant()?->id;


            });


            static::addGlobalScope('tenant', function ($builder) {


                if (tenant()) {


                    $builder->where('tenant_id', tenant()->id);


                }


            });


        }


        public function portfolios(): HasMany


        {


            return $this->hasMany(Portfolio::class, 'photographer_id');


        }


        public function bookings(): HasMany


        {


            return $this->hasMany(Booking::class, 'photographer_id');


        }
}
