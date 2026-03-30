<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiFleet extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;


        protected $table = 'taxi_fleets';


        protected $fillable = [


            'tenant_id',


            'name',


            'company_name',


            'vehicle_count',


            'rating',


            'correlation_id',


            'tags',


        ];


        protected $casts = [


            'tags' => 'collection',


            'rating' => 'float',


            'vehicle_count' => 'integer',


        ];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));


        }


        public function vehicles(): HasMany


        {


            return $this->hasMany(TaxiVehicle::class, 'fleet_id');


        }
}
