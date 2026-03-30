<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrReview extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;


        protected $table = 'str_reviews';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'booking_id',


            'apartment_id',


            'user_id',


            'rating',


            'comment',


            'media',


            'correlation_id',


        ];


        protected $casts = [


            'rating' => 'integer',


            'media' => 'json',


        ];


        protected static function booted(): void


        {


            static::creating(function (self $model) {


                $model->uuid ??= (string) Str::uuid();


                $model->correlation_id ??= request()->header('X-Correlation-ID', (string) Str::uuid());


                $model->tenant_id ??= tenant()->id ?? null;


            });


            static::addGlobalScope('tenant', function (Builder $builder) {


                if (tenant()) {


                    $builder->where('tenant_id', tenant()->id);


                }


            });


        }


        public function apartment(): BelongsTo


        {


            return $this->belongsTo(StrApartment::class, 'apartment_id');


        }


        public function booking(): BelongsTo


        {


            return $this->belongsTo(StrBooking::class, 'booking_id');


        }
}
