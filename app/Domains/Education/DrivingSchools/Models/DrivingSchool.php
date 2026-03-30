<?php declare(strict_types=1);

namespace App\Domains\Education\DrivingSchools\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DrivingSchool extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='driving_schools';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','categories','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['categories'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('driving_schools.tenant_id',tenant()->id));}
}
