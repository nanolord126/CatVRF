<?php declare(strict_types=1);

namespace App\Domains\Sports\Billiards\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BilliardHall extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='billiard_halls';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','name','address','price_kopecks_per_hour','table_count','rating','tags'];protected $casts=['price_kopecks_per_hour'=>'integer','table_count'=>'integer','rating'=>'float','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('billiard_halls.tenant_id',tenant()->id));}
}
