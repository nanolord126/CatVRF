<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Booking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;


        protected $table = 'photography_bookings';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'client_id',


            'session_id',


            'photographer_id',


            'studio_id',


            'starts_at',


            'ends_at',


            'status',


            'total_amount_kopecks',


            'paid_amount_kopecks',


            'idempotency_key',


            'correlation_id'


        ];


        protected $casts = [


            'uuid' => 'string',


            'starts_at' => 'datetime',


            'ends_at' => 'datetime',


            'total_amount_kopecks' => 'integer',


            'paid_amount_kopecks' => 'integer',


        ];


        protected static function booted(): void


        {


            static::creating(function (self $model) {


                $model->uuid ??= (string) Str::uuid();


                $model->tenant_id ??= tenant()?->id;


                $model->idempotency_key ??= (string) Str::uuid();


            });


            static::addGlobalScope('tenant', function ($builder) {


                if (tenant()) {


                    $builder->where('tenant_id', tenant()->id);


                }


            });


        }


        public function session(): BelongsTo


        {


            return $this->belongsTo(PhotoSession::class, 'session_id');


        }


        public function photographer(): BelongsTo


        {


            return $this->belongsTo(Photographer::class, 'photographer_id');


        }


        public function studio(): BelongsTo


        {


            return $this->belongsTo(PhotoStudio::class, 'studio_id');


        }
}
