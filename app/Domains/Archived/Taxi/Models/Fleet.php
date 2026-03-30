<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Fleet extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes, LogsActivity;


        protected $table = 'taxi_fleets';


        protected $fillable = [


            'uuid',


            'tenant_id',


            'name',


            'inn',


            'commission_rate',


            'settings',


            'status',


            'correlation_id',


            'tags'


        ];


        protected $casts = [


            'settings' => 'json',


            'tags' => 'json',


            'commission_rate' => 'float',


            'tenant_id' => 'integer'


        ];


        protected $hidden = ['settings'];


        /**


         * Глобальный скоупинг тенанта.


         */


        protected static function booted(): void


        {


            static::creating(function (Fleet $fleet) {


                $fleet->uuid = $fleet->uuid ?? (string) Str::uuid();


                $fleet->tenant_id = $fleet->tenant_id ?? (tenant()->id ?? 1);


                $fleet->status = $fleet->status ?? 'active';


                $fleet->correlation_id = $fleet->correlation_id ?? request()->header('X-Correlation-ID');


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


                ->logOnly(['name', 'status', 'commission_rate'])


                ->logOnlyDirty()


                ->dontSubmitEmptyLogs()


                ->setLogName('taxi_management');


        }


        /**


         * Отношения.


         */


        public function drivers(): HasMany


        {


            return $this->hasMany(Driver::class, 'fleet_id');


        }


        public function activeDrivers(): HasMany


        {


            return $this->hasMany(Driver::class, 'fleet_id')->where('is_active', true);


        }


        /**


         * Расчёт выплат.


         */


        public function calculateNetIncome(int $grossAmount): int


        {


            return (int) ($grossAmount * (1 - ($this->commission_rate / 100)));


        }
}
