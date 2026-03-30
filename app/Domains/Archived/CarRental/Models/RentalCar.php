<?php declare(strict_types=1);

namespace App\Domains\Archived\CarRental\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RentalCar extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

        protected $table = 'rental_cars';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'correlation_id',
            'brand',
            'model',
            'year',
            'license_plate',
            'price_per_day',
            'is_available',
            'tags',
            'meta',
        ];

        protected $casts = [
            'is_available' => 'boolean',
            'tags' => 'json',
            'meta' => 'json',
            'price_per_day' => 'integer',
            'year' => 'integer',
        ];
    }


    use Illuminate\Database\Eloquent\Model;


    use Illuminate\Database\Eloquent\SoftDeletes;


    use App\Traits\TenantScoped;


    final class RentalCar extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='rental_cars';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','make','model','year','license_plate','price_kopecks_per_day','status','rating','tags'];protected $casts=['price_kopecks_per_day'=>'integer','year'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('rental_cars.tenant_id',tenant()->id));}
}
