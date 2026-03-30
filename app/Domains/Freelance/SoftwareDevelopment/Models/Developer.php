<?php declare(strict_types=1);

namespace App\Domains\Freelance\SoftwareDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Developer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='developers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','technologies','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['technologies'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('developers.tenant_id',tenant()->id));}
}
