declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\GiftDelivery\Services;
use App\Domains\GiftDelivery\Models\GiftVendor;
use App\Domains\GiftDelivery\Models\GiftOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * GiftDeliveryService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GiftDeliveryService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createOrder(int $vendorId,$giftType,$recipientAddress,$deliveryDate,string $correlationId=""):GiftOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("gift:order:".auth()->id(),15))throw new \RuntimeException("Too many",429);RateLimiter::hit("gift:order:".auth()->id(),3600);
return $this->db->transaction(function()use($vendorId,$giftType,$recipientAddress,$deliveryDate,$correlationId){$v=GiftVendor::findOrFail($vendorId);$total=299900;$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'gift_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=GiftOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'vendor_id'=>$vendorId,'sender_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','gift_type'=>$giftType,'recipient_address'=>$recipientAddress,'delivery_date'=>$deliveryDate,'tags'=>['gift'=>true]]);$this->log->channel('audit')->info('Gift order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):GiftOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=GiftOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'gift_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Gift order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):GiftOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=GiftOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'gift_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Gift order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):GiftOrder{return GiftOrder::findOrFail($orderId);}
public function getUserOrders(int $senderId){return GiftOrder::where('sender_id',$senderId)->orderBy('created_at','desc')->take(10)->get();}
}
