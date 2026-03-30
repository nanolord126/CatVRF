<?php declare(strict_types=1);

namespace App\Domains\RealEstate\OfficeRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CoworkingSpace extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='coworking_spaces';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','seats_count','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['seats_count'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('coworking_spaces.tenant_id',tenant()->id));}
}
