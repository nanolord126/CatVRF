declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Toys\Services;

use App\Domains\Toys\Models\Toy;
use App\Domains\Toys\Models\ToyOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final /**
 * ToysService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ToysService {
    public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
    public function createOrder(int $sellerId,array $items,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("toys:order:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("toys:order:".auth()->id(),3600);
        return $this->db->transaction(function()use($sellerId,$items,$correlationId){$total=0;foreach($items as $item){$t=Toy::where('id',$item['toy_id'])->firstOrFail();$total+=$t->price_kopecks*$item['quantity'];if($t->stock<$item['quantity'])throw new \RuntimeException("Out of stock",400);}$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'toy_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block'){$this->log->channel('audit')->error('Toy order blocked',['user_id'=>auth()->id(),'correlation_id'=>$correlationId]);throw new \RuntimeException("Security",403);}$o=ToyOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'seller_id'=>$sellerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','items_json'=>$items,'tags'=>['toys'=>true]]);$this->log->channel('audit')->info('Toy order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
    }
    public function completeOrder(int $orderId,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=ToyOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);foreach($o->items_json as $i){Toy::findOrFail($i['toy_id'])->decrement('stock',$i['quantity']);}$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'toy_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Toy completed',['order_id'=>$o->id]);return $o;});}
    public function cancelOrder(int $orderId,string $correlationId=""):ToyOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=ToyOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed'){$this->wallet->credit(tenant()->id,$o->total_kopecks,'toy_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);}$this->log->channel('audit')->info('Toy cancelled',['order_id'=>$o->id]);return $o;});}
    public function getOrder(int $orderId):ToyOrder{return ToyOrder::findOrFail($orderId);}
    public function getUserOrders(int $clientId){return ToyOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
