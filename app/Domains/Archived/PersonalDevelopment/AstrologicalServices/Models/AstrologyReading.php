<?php declare(strict_types=1);

namespace App\Domains\Archived\PersonalDevelopment\AstrologicalServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AstrologyReading extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='astrology_readings';protected $fillable=['uuid','tenant_id','astrologer_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','reading_type','reading_hours','reading_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','reading_hours'=>'integer','reading_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('astrology_readings.tenant_id',tenant()->id));}
}
