<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Babysitting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Babysitter extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='babysitters';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','experience_years','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['experience_years'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('babysitters.tenant_id',tenant()->id));}
}
