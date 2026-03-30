<?php declare(strict_types=1);

namespace App\Domains\Archived\Pharmacy\MedicalSupplies\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Medicine extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;


        protected $table = 'medicines'; protected $fillable = ['uuid','tenant_id','pharmacy_id','correlation_id','name','price_kopecks','requires_rx','stock','tags'];


        protected $casts = ['price_kopecks'=>'integer','requires_rx'=>'boolean','stock'=>'integer','tags'=>'json'];


        protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('medicines.tenant_id',tenant()->id));}
}
