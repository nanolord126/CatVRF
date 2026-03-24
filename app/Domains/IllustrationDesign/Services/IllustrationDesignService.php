<?php declare(strict_types=1);
namespace App\Domains\IllustrationDesign\Services;
use App\Domains\IllustrationDesign\Models\Illustrator;
use App\Domains\IllustrationDesign\Models\IllustrationOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class IllustrationDesignService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createOrder(int $illustratorId,$style,$illustrationCount,$dueDate,string $correlationId=""):IllustrationOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("illust:order:".auth()->id(),17))throw new \RuntimeException("Too many",429);RateLimiter::hit("illust:order:".auth()->id(),3600);
return DB::transaction(function()use($illustratorId,$style,$illustrationCount,$dueDate,$correlationId){$i=Illustrator::findOrFail($illustratorId);$total=(int)($i->price_kopecks_per_illustration*$illustrationCount);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'illustration','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=IllustrationOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'illustrator_id'=>$illustratorId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','style'=>$style,'illustration_count'=>$illustrationCount,'due_date'=>$dueDate,'tags'=>['illustration'=>true]]);Log::channel('audit')->info('Illustration order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):IllustrationOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=IllustrationOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'illust_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Illustration order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):IllustrationOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=IllustrationOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'illust_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Illustration order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):IllustrationOrder{return IllustrationOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return IllustrationOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
