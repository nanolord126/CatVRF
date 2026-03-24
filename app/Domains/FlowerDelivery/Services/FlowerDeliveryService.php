<?php declare(strict_types=1);
namespace App\Domains\FlowerDelivery\Services;
use App\Domains\FlowerDelivery\Models\FloristShop;
use App\Domains\FlowerDelivery\Models\BouquetOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
final class FlowerDeliveryService{public function __construct(private readonly FraudControlService $fraud,private readonly WalletService $wallet){}
public function createOrder(int $shopId,$bouquetType,$recipientAddress,$deliveryDate,string $correlationId=""):BouquetOrder{$correlationId=$correlationId?:(string)Str::uuid();if(RateLimiter::tooManyAttempts("flower:order:".auth()->id(),16))throw new \RuntimeException("Too many",429);RateLimiter::hit("flower:order:".auth()->id(),3600);
return DB::transaction(function()use($shopId,$bouquetType,$recipientAddress,$deliveryDate,$correlationId){$s=FloristShop::findOrFail($shopId);$total=$s->price_kopecks_per_bouquet;$fraud=$this->fraud->check(['user_id'=>auth()->id()??0,'operation_type'=>'flower_order','correlation_id'=>$correlationId,'amount'=>$total]);if($fraud['decision']==='block')throw new \RuntimeException("Security",403);$o=BouquetOrder::create(['uuid'=>Str::uuid(),'tenant_id'=>tenant()->id,'shop_id'=>$shopId,'customer_id'=>auth()->id()??0,'correlation_id'=>$correlationId,'status'=>'pending_payment','total_kopecks'=>$total,'payout_kopecks'=>$total-(int)($total*0.14),'payment_status'=>'pending','bouquet_type'=>$bouquetType,'recipient_address'=>$recipientAddress,'delivery_date'=>$deliveryDate,'tags'=>['flower'=>true]]);Log::channel('audit')->info('Bouquet order created',['order_id'=>$o->id,'correlation_id'=>$correlationId]);return $o;});
}
public function completeOrder(int $orderId,string $correlationId=""):BouquetOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=BouquetOrder::findOrFail($orderId);if($o->payment_status!=='completed')throw new \RuntimeException("Not paid",400);$o->update(['status'=>'completed','correlation_id'=>$correlationId]);$this->wallet->credit(tenant()->id,$o->payout_kopecks,'flower_payout',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Bouquet order completed',['order_id'=>$o->id]);return $o;});}
public function cancelOrder(int $orderId,string $correlationId=""):BouquetOrder{$correlationId=$correlationId?:(string)Str::uuid();return DB::transaction(function()use($orderId,$correlationId){$o=BouquetOrder::findOrFail($orderId);if($o->status==='completed')throw new \RuntimeException("Cannot cancel",400);$o->update(['status'=>'cancelled','payment_status'=>'refunded','correlation_id'=>$correlationId]);if($o->payment_status==='completed')$this->wallet->credit(tenant()->id,$o->total_kopecks,'flower_refund',['correlation_id'=>$correlationId,'order_id'=>$o->id]);Log::channel('audit')->info('Bouquet order cancelled',['order_id'=>$o->id]);return $o;});}
public function getOrder(int $orderId):BouquetOrder{return BouquetOrder::findOrFail($orderId);}
public function getUserOrders(int $customerId){return BouquetOrder::where('customer_id',$customerId)->orderBy('created_at','desc')->take(10)->get();}
}
