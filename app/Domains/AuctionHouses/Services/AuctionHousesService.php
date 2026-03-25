declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\AuctionHouses\Services;
use App\Domains\AuctionHouses\Models\Auction;
use App\Domains\AuctionHouses\Models\Bid;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * AuctionHousesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AuctionHousesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createBid(int $auctionId,$bidAmount,string $correlationId=""):Bid{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("auction:bid:".auth()->id(),40))throw new \RuntimeException("Too many",429);RateLimiter::hit("auction:bid:".auth()->id(),3600);
return $this->db->transaction(function()use($auctionId,$bidAmount,$correlationId){$a=Auction::findOrFail($auctionId);if($bidAmount<=$a->current_bid)throw new \RuntimeException("Bid too low",400);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'auction_bid','correlation_id'=>$correlationId,'amount'=>$bidAmount]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$b=Bid::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'auction_id'=>$auctionId,'bidder_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'bid_amount'=>$bidAmount,'payment_status'=>'pending','tags'=>['auction'=>true]]);$a->update(['current_bid'=>$bidAmount]);$this->log->channel('audit')->info('Auction bid created',['bid_id'=>$b->id,'correlation_id'=>$correlationId]);return $b;});
}
public function completeBid(int $bidId,string $correlationId=""):Bid{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bidId,$correlationId){$b=Bid::findOrFail($bidId);if($b->payment_status==='completed')throw new \RuntimeException("Already paid",400);$b->update(['payment_status'=>'completed','correlation_id'=>$correlationId]);$payout=(int)($b->bid_amount*0.86);$this->wallet->credit(tenant()->id,$payout,'auction_payout',['correlation_id'=>$correlationId,'bid_id'=>$b->id]);$this->log->channel('audit')->info('Auction bid completed',['bid_id'=>$b->id]);return $b;});}
public function cancelBid(int $bidId,string $correlationId=""):Bid{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($bidId,$correlationId){$b=Bid::findOrFail($bidId);if($b->payment_status==='completed')throw new \RuntimeException("Cannot cancel paid",400);$b->delete();$this->log->channel('audit')->info('Auction bid cancelled',['bid_id'=>$b->id]);return $b;});}
public function getBid(int $bidId):Bid{return Bid::findOrFail($bidId);}
public function getAuctionBids(int $auctionId){return Bid::where('auction_id',$auctionId)->orderBy('bid_amount','desc')->take(20)->get();}
}
