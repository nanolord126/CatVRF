<?php declare(strict_types=1);

namespace App\Domains\Consulting\CloudStorage\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StorageProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='storage_providers';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','storage_gb','price_kopecks_per_month','rating','is_verified','tags'];protected $casts=['storage_gb'=>'integer','price_kopecks_per_month'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('storage_providers.tenant_id',tenant()->id));}
}
