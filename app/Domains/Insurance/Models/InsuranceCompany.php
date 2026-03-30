<?php declare(strict_types=1);

namespace App\Domains\Insurance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InsuranceCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='insurance_companies';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','products','rating','is_verified','tags'];protected $casts=['products'=>'json','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('insurance_companies.tenant_id',tenant()->id));}
}
