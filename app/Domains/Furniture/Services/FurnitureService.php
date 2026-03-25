declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use App\Domains\Furniture\Models\FurnitureItem;
use App\Domains\Furniture\Models\FurnitureOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final /**
 * FurnitureService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FurnitureService {
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
    public function createOrder(int $sellerId,array $items,string $correlationId=""):FurnitureOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("furn:order:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("furn:order:".auth()->id(),3600);
        return $this->db->transaction(function()use($sellerId,$items,$correlationId){$total=0;foreach($items as $item){$f=FurnitureItem::where('id',$item['item_id'])->firstOrFail();$total+=$f->price_kopecks*$item['quantity'];if($f->stock<$item['quantity'])throw new \RuntimeException("Out of stock",400);}$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'furniture_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block'){$this->log->channel('audit')->error('Furniture order blocked',['user_id'=>auth()->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security",403);}$o=FurnitureOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'seller_id'=>$sellerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['furniture'=>true]]);$this->log->channel('audit')->info('Furniture order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
    }
    public function completeOrder(int $orderId,string $correlationId=""):FurnitureOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=FurnitureOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){FurnitureItem::findOrFail($i['item_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'furn_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Furniture completed',['order_id'=>$o->id]);return $o;});}
    public function cancelOrder(int $orderId,string $correlationId=""):FurnitureOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=FurnitureOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,'furn_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);}$this->log->channel('audit')->info('Furniture cancelled',['order_id'=>$o->id]);return $o;});}
    public function getOrder(int $orderId):FurnitureOrder{return FurnitureOrder::findOrFail($orderId);}
    public function getUserOrders(int $clientId){return FurnitureOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
