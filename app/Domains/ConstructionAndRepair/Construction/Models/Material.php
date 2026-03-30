<?php declare(strict_types=1);

namespace App\Domains\ConstructionAndRepair\Construction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Material extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;
        protected $table = 'construction_materials'; protected $fillable = ['uuid','tenant_id','supplier_id','correlation_id','name','price_kopecks','unit','stock','tags'];
        protected $casts = ['price_kopecks'=>'integer','stock'=>'integer','tags'=>'json'];
        protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('construction_materials.tenant_id',tenant()->id));}
}
