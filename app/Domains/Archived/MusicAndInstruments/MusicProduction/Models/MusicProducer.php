<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\MusicProduction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicProducer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='music_producers';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','genres','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['genres'=>'json','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('music_producers.tenant_id',tenant()->id));}
}
