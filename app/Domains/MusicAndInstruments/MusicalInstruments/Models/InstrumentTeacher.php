<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicalInstruments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class InstrumentTeacher extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='instrument_teachers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','instruments','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['instruments'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('instrument_teachers.tenant_id',tenant()->id));}
}
