<?php declare(strict_types=1);

namespace App\Domains\Logistics\MovingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MovingCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='moving_companies';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','trucks_count','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['trucks_count'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('moving_companies.tenant_id',tenant()->id));}
}
