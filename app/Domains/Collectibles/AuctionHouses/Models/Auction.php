<?php declare(strict_types=1);

namespace App\Domains\Collectibles\AuctionHouses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Auction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='auctions';protected $fillable=['uuid','tenant_id','owner_id','correlation_id','item_name','start_price','current_bid','end_time','status','tags'];protected $casts=['start_price'=>'integer','current_bid'=>'integer','end_time'=>'datetime','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('auctions.tenant_id',tenant()->id));}
}
