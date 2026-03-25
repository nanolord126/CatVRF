declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\MovingServices\Services;
use App\Domains\MovingServices\Models\MovingCompany;
use App\Domains\MovingServices\Models\MovingOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * MovingServicesService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class MovingServicesService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createOrder(int $companyId,$moveDate,$durationHours,$fromAddress,$toAddress,string $correlationId=""):MovingOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("moving:order:".auth()->id(),5))throw new \RuntimeException("Too many",429);RateLimiter::hit("moving:order:".auth()->id(),3600);
return $this->db->transaction(function()use($companyId,$moveDate,$durationHours,$fromAddress,$toAddress,$correlationId){$c=MovingCompany::findOrFail($companyId);$total=(int)($c->price_kopecks_per_hour*$durationHours);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'moving_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=MovingOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'company_id'=>$companyId,'customer_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','move_date'=>$moveDate,'duration_hours'=>$durationHours,'from_address'=>$fromAddress,'to_address'=>$toAddress,'tags'=>['moving'=>true]]);$this->log->channel('audit')->info('Moving order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):MovingOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=MovingOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'moving_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Moving order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):MovingOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=MovingOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'moving_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Moving order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):MovingOrder{return MovingOrder::findOrFail($orderId);}
public function getUserOrders(int $customerId){return MovingOrder::where('customer_id',$customerId)->orderBy('created_at','desc')->take(10)->get();}
}
