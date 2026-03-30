<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogisticCompany extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='logistic_companies';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_km','vehicles_count','rating','tags'];protected $casts=['price_kopecks_per_km'=>'integer','vehicles_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('logistic_companies.tenant_id',tenant()->id));}
}
