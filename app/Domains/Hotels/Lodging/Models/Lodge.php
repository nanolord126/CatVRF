<?php declare(strict_types=1);

namespace App\Domains\Hotels\Lodging\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Lodge extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='lodges';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_night','rooms','is_verified','tags'];protected $casts=['price_kopecks_per_night'=>'integer','rooms'=>'integer','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('lodges.tenant_id',tenant()->id));}
}
