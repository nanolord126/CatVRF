<?php declare(strict_types=1);

namespace App\Domains\Freelance\WritingServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WritingOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='writing_orders';protected $fillable=['uuid','tenant_id','writer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','word_count','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','word_count'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('writing_orders.tenant_id',tenant()->id));}
}
