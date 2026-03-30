<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Driver extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, LogsActivity;


        protected $table = 'taxi_drivers';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'user_id',


            'fleet_id',


            'license_number',


            'is_active',


            'is_available',


            'rating',


            'current_location_point',


            'balance_meta',


            'correlation_id',


            'tags'


        ];


        protected $casts = [


            'balance_meta' => 'json',


            'tags' => 'json',


            'is_active' => 'boolean',


            'is_available' => 'boolean',


            'rating' => 'float',


            'tenant_id' => 'integer',


            'user_id' => 'integer'


        ];


        protected $hidden = ['balance_meta'];


        /**


         * Глобальный скоупинг тенанта.


         */


        protected static function booted(): void


        {


            static::creating(function (Driver $driver) {


                $driver->uuid = $driver->uuid ?? (string) Str::uuid();


                $driver->tenant_id = $driver->tenant_id ?? (tenant()->id ?? 1);


                $driver->correlation_id = $driver->correlation_id ?? request()->header('X-Correlation-ID');


            });


            static::addGlobalScope('tenant', function ($query) {


                if (tenant()) {


                    $query->where('tenant_id', tenant()->id);


                }


            });


        }


        /**


         * Настройка логов активности.


         */


        public function getActivitylogOptions(): LogOptions


        {


            return LogOptions::defaults()


                ->logOnly(['is_active', 'is_available', 'rating', 'fleet_id'])


                ->logOnlyDirty()


                ->dontSubmitEmptyLogs()


                ->setLogName('driver_tracking');


        }


        /**


         * Отношения.


         */


        public function user(): BelongsTo


        {


            return $this->belongsTo(User::class);


        }


        public function fleet(): BelongsTo


        {


            return $this->belongsTo(Fleet::class);


        }


        public function vehicle(): HasOne


        {


            return $this->hasOne(Vehicle::class, 'driver_id')->where('status', 'active');


        }


        public function rides(): HasMany


        {


            return $this->hasMany(TaxiRide::class, 'driver_id');


        }


        public function deliveryOrders(): HasMany


        {


            return $this->hasMany(DeliveryOrder::class, 'courier_id');


        }


        /**


         * Обновить координаты.


         */


        public function updateLocation(float $lat, float $lon): void


        {


            $this->update([


                'current_location_point' => "{$lat},{$lon}",


                'updated_at' => now()


            ]);


        }
}
