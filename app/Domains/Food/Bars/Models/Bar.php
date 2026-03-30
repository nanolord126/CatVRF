<?php declare(strict_types=1);

namespace App\Domains\Food\Bars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Bar extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='bars';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_drink','min_age','rating','tags'];protected $casts=['price_kopecks_per_drink'=>'integer','min_age'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bars.tenant_id',tenant()->id));}
}
