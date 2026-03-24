<?php declare(strict_types=1);
namespace App\Domains\Laundry\Services;
use App\Domains\Laundry\Models\LaundryShop;
use App\Domains\Laundry\Models\LaundryOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class LaundryService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createOrder(int $shopId,$weightKg,$pickupDate,$deliveryDate,string $correlationId=""):LaundryOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("laundry:order:".auth()->id(),18))throw new \RuntimeException("Too many",429);RateLimiter::hit("laundry:order:".auth()->id(),3600);
return DB::transaction(function()use($shopId,$weightKg,$pickupDate,$deliveryDate,$correlationId){$s=LaundryShop::findOrFail($shopId);$total=(int)(($s->price_kopecks_per_kg*$weightKg)+$s->delivery_fee);$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'laundry_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=LaundryOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'shop_id'=>$shopId,'client_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','weight_kg'=>$weightKg,'pickup_date'=>$pickupDate,'delivery_date'=>$deliveryDate,'tags'=>['laundry'=>true]]);Log::channel('audit')->info('Laundry order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):LaundryOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=LaundryOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'laundry_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Laundry order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):LaundryOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=LaundryOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'laundry_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Laundry order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):LaundryOrder{return LaundryOrder::findOrFail($orderId);}
public function getUserOrders(int $clientId){return LaundryOrder::where('client_id',$clientId)->orderBy('created_at','desc')->take(10)->get();}
}
