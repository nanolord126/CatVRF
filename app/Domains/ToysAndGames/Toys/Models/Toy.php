<?php declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Toy extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes, TenantScoped;
        protected $table = 'toys'; protected $fillable = ['uuid','tenant_id','seller_id','correlation_id','name','price_kopecks','age_from','age_to','stock','tags'];
        protected $casts = ['price_kopecks'=>'integer','age_from'=>'integer','age_to'=>'integer','stock'=>'integer','tags'=>'json'];
        protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('toys.tenant_id',tenant()->id));}
}
