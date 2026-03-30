<?php declare(strict_types=1);

namespace App\Domains\CleaningServices\Cleaning\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CleaningService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='cleaning_services';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','service_type','price_kopecks_per_hour','workers_count','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','workers_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('cleaning_services.tenant_id',tenant()->id));}
}
