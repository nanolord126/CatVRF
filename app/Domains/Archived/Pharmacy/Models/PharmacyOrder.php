<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PharmacyOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'pharmacy_orders';


        protected $fillable = [


            'tenant_id',


            'user_id',


            'pharmacy_id',


            'uuid',


            'total_amount',


            'status',


            'idempotency_key',


            'tags',


            'correlation_id'


        ];


        protected $casts = [


            'total_amount' => 'integer',


            'tags' => 'json'


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


        public function user(): BelongsTo


        {


            return $this->belongsTo(User::class, 'user_id');


        }


        public function pharmacy(): BelongsTo


        {


            return $this->belongsTo(Pharmacy::class);


        }


        public function items(): HasMany


        {


            return $this->hasMany(PharmacyOrderItem::class, 'order_id');


        }
}
