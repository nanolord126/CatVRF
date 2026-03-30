<?php declare(strict_types=1);

namespace App\Domains\Medical\NursingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class NursingAgency extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='nursing_agencies';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','qualifications','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['qualifications'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('nursing_agencies.tenant_id',tenant()->id));}
}
