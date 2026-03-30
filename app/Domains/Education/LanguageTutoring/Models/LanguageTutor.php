<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageTutoring\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageTutor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='language_tutors';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','languages','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['languages'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('language_tutors.tenant_id',tenant()->id));}
}
