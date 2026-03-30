<?php declare(strict_types=1);

namespace App\Domains\Freelance\Copywriting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Copywriter extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='copywriters';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','specializations','price_kopecks_per_word','rating','is_verified','tags'];protected $casts=['specializations'=>'json','price_kopecks_per_word'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('copywriters.tenant_id',tenant()->id));}
}
