declare(strict_types=1);

<?php declare(strict_types=1);
namespace App\Domains\GraphicsDesign\Services;
use App\Domains\GraphicsDesign\Models\GraphicDesigner;
use App\Domains\GraphicsDesign\Models\GraphicDesignOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * GraphicsDesignService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GraphicsDesignService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
public function createOrder(int $designerId,$designType,$designCount,$dueDate,string $correlationId=""):GraphicDesignOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("gdesign:order:".auth()->id(),19))throw new \RuntimeException("Too many",429);RateLimiter::hit("gdesign:order:".auth()->id(),3600);
return $this->db->transaction(function()use($designerId,$designType,$designCount,$dueDate,$correlationId){$d=GraphicDesigner::findOrFail($designerId);$total=(int)($d->price_kopecks_per_design*$designCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'graphic_design','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=GraphicDesignOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'designer_id'=>$designerId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','design_type'=>$designType,'design_count'=>$designCount,'due_date'=>$dueDate,'tags'=>['graphics'=>true]]);$this->log->channel('audit')->info('Graphic design order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):GraphicDesignOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=GraphicDesignOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'gdesign_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Graphic design order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):GraphicDesignOrder{$correlationId=$correlationId?:(string)Str::uuid();return $this->db->transaction(function()use($orderId,$correlationId){$o=GraphicDesignOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'gdesign_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);$this->log->channel('audit')->info('Graphic design order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):GraphicDesignOrder{return GraphicDesignOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return GraphicDesignOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
