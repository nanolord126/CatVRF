<?php declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\BoardGames\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BoardGameCafe extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='board_game_cafes';protected $fillable=['uuid','tenant_id','user_id','correlation_id','name','table_count','price_kopecks_per_hour','rating','is_verified','tags'];protected $casts=['table_count'=>'integer','price_kopecks_per_hour'=>'integer','rating'=>'float','is_verified'=>'boolean','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('board_game_cafes.tenant_id',tenant()->id));}
}
