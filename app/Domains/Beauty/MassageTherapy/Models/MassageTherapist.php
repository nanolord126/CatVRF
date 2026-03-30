<?php declare(strict_types=1);

namespace App\Domains\Beauty\MassageTherapy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MassageTherapist extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='massage_therapists';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','massage_types','price_kopecks_per_minute','rating','is_verified','tags'];protected $casts=['massage_types'=>'json','price_kopecks_per_minute'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('massage_therapists.tenant_id',tenant()->id));}
}
