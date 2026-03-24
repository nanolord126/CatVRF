<?php declare(strict_types=1);
namespace App\Domains\BoardGames\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
final class BoardGameCafe extends Model{use HasUuids,SoftDeletes,TenantScoped;protected $table='board_game_cafes';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','table_count','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['table_count'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('board_game_cafes.tenant_id',tenant()->id));}}
