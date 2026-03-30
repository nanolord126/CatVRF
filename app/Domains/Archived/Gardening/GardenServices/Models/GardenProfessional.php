<?php declare(strict_types=1);

namespace App\Domains\Archived\Gardening\GardenServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GardenProfessional extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='garden_professionals';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','services','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['services'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('garden_professionals.tenant_id',tenant()->id));}
}
