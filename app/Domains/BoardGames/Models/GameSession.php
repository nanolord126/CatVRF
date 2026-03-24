<?php declare(strict_types=1);
namespace App\Domains\BoardGames\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class GameSession extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='game_sessions';protected $fillable=['uuid','tenant_id','cafe_id','client_id','correlation_id','status','total_kopecks','payout_kopecks','payment_status','session_date','duration_hours','table_number','tags'];protected $casts=['total_kopecks'=>'integer','payout_kopecks'=>'integer','session_date'=>'datetime','duration_hours'=>'integer','table_number'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('game_sessions.tenant_id',tenant()->id));}}
