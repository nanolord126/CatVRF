<?php declare(strict_types=1);

namespace App\Domains\Collectibles\AuctionHouses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Bid extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids,SoftDeletes,TenantScoped;protected $table='bids';protected $fillable=['uuid','tenant_id','auction_id','bidder_id','correlation_id','bid_amount','payment_status','tags'];protected $casts=['bid_amount'=>'integer','tags'=>'json'];protected static function booted(){static::addGlobalScope('tenant',fn($q)=>$q->where('bids.tenant_id',tenant()->id));}
}
