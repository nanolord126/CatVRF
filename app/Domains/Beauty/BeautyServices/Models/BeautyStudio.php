<?php declare(strict_types=1);

namespace App\Domains\Beauty\BeautyServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyStudio extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='beauty_studios';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','address','services','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('beauty_studios.tenant_id',tenant()->id));}
}
