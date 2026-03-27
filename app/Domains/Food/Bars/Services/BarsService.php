<?php

declare(strict_types=1);

namespace App\Domains\Food\Bars\Services;
use App\Domains\Food\Bars\Models\Bar;
use App\Domains\Food\Bars\Models\BarOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final /**
 * BarsService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BarsService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet) {}
public function createOrder(int $barId,$items,string $correlationId=""):BarOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("bar:order:".auth()->id(),30))throw new \RuntimeException("Too many",429);RateLimiter::hit("bar:order:".auth()->id(),3600);
return DB::transaction(function()use($barId,$items,$correlationId){$bar=Bar::findOrFail($barId);$total=0;foreach($items as $item){$total+=$bar->price_kopecks_per_drink*$item['qty'];}$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'bar_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=BarOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'bar_id'=>$barId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','order_date'=>now(),'tags'=>['bar'=>true]]);Log::channel('audit')->info('Bar order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):BarOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=BarOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'bar_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Bar order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):BarOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=BarOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'bar_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Bar order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):BarOrder{return BarOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return BarOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
