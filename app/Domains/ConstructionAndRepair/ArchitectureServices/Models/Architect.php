<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\ArchitectureServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Architect extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='architects';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','price_kopecks_per_sqm','rating','is_verified','tags'];protected $casts=['specializations'=>'json','price_kopecks_per_sqm'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('architects.tenant_id',tenant()->id));}
}
