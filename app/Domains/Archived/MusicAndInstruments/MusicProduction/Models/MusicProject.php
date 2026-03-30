<?php declare(strict_types=1);

namespace App\Domains\Archived\MusicAndInstruments\MusicProduction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicProject extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='music_projects';protected $fillable=['uuid','tenant_id','producer_id','artist_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','project_type','production_hours','due_date','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','production_hours'=>'integer','due_date'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('music_projects.tenant_id',tenant()->id));}
}
