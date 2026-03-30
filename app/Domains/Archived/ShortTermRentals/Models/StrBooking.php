<?php declare(strict_types=1);

namespace App\Domains\Archived\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;


        protected $table = 'str_bookings';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'business_group_id',


            'apartment_id',


            'user_id',


            'check_in',


            'check_out',


            'status',


            'total_price',


            'deposit_amount',


            'deposit_status',


            'payment_status',


            'payout_at',


            'is_b2b',


            'metadata',


            'correlation_id',


        ];


        protected $casts = [


            'check_in' => 'datetime',


            'check_out' => 'datetime',


            'payout_at' => 'datetime',


            'total_price' => 'integer',


            'deposit_amount' => 'integer',


            'is_b2b' => 'boolean',


            'metadata' => 'json',


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


        public function user(): BelongsTo


        {


            return $this->belongsTo(User::class, 'user_id');


        }


        public function reviews(): HasMany


        {


            return $this->hasMany(StrReview::class, 'booking_id');


        }


        /**


         * Проверка: залог вхолдирован


         */


        public function isDepositHeld(): bool


        {


            return $this->deposit_status === 'held';


        }


        /**


         * Проверка: проживание завершено и готово к выплате


         */


        public function isReadyForPayout(): bool


        {


            return $this->status === 'completed' && $this->payout_at && $this->payout_at->isPast();


        }
}
